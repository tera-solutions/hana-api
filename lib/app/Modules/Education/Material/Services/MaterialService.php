<?php

namespace App\Modules\Education\Material\Services;

use App\Helpers\Task;
use App\Modules\Education\Material\Enums\MaterialEntityType;
use App\Modules\Education\Material\Models\Material;
use App\Modules\Education\Material\Models\MaterialMapping;
use App\Modules\Education\Material\Models\MaterialVersion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Package\Database\Concerns\HandlesEntityQueries;

class MaterialService
{
    use HandlesEntityQueries;

    /**
     * Paginated, searchable, filterable list (material.md §12).
     */
    public function paginate(array $params = [])
    {
        $query = Material::query();

        if (! empty($params['search'])) {
            $search = $params['search'];
            $query->where(function ($q) use ($search) {
                $q->where('material_code', 'like', "%{$search}%")
                    ->orWhere('material_name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        foreach (['category_id', 'material_type', 'access_type', 'status'] as $filter) {
            if (! empty($params[$filter])) {
                $query->where($filter, $params[$filter]);
            }
        }

        // Scope to materials linked to a specific entity (course/lesson_plan/lesson/…),
        // e.g. the teacher app's classroom "Tài liệu" tab filtering by the class's course.
        if (! empty($params['entity_type']) && ! empty($params['entity_id'])) {
            $entityType = $params['entity_type'];
            $entityId = $params['entity_id'];
            $query->whereExists(function ($sub) use ($entityType, $entityId) {
                $sub->selectRaw('1')
                    ->from('edu_material_mappings')
                    ->whereColumn('edu_material_mappings.material_id', 'edu_materials.id')
                    ->where('edu_material_mappings.entity_type', $entityType)
                    ->where('edu_material_mappings.entity_id', $entityId);
            });
        }

        $this->applySort($query, $params, ['material_code', 'material_name', 'material_type', 'current_version', 'status', 'created_at']);

        return $query->with(['category', 'versions'])->paginate($this->resolvePerPage($params));
    }

    public function find($id): Material
    {
        return Material::with('category', 'versions')->findOrFail($id);
    }

    /**
     * Detail with versions and where-used mappings (material.md §11).
     */
    public function detail($id): array
    {
        $material = Material::with(['category', 'versions', 'mappings'])->findOrFail($id);

        return [
            'material' => $material,
            'usage' => $material->mappings->groupBy('entity_type')->map->count(),
        ];
    }

    public function create(array $data): Material
    {
        return DB::transaction(function () use ($data) {
            // File fields belong to the version row, not edu_materials.
            $fileKeys = ['file_id', 'file_name', 'file_size', 'mime_type', 'change_log'];
            $fileData = array_intersect_key($data, array_flip($fileKeys));

            $material = new Material(array_diff_key($data, array_flip($fileKeys)));
            $material->material_code = $this->generateCode();
            $material->status = Material::STATUS_DRAFT;
            $material->current_version = 0;
            $material->save();

            // First upload is optional at creation (material.md §7).
            if (! empty($fileData['file_id']) || ! empty($fileData['file_name'])) {
                $this->addVersion($material->id, $fileData);
            }

            return $this->find($material->id);
        });
    }

    /**
     * Generate the next human-readable material code (e.g. MAT000001).
     */
    private function generateCode(): string
    {
        $count = Task::setAndGetReferenceCount('material');

        return Task::generateReferenceNumber('material', $count, 'MAT');
    }

    public function update($id, array $data): Material
    {
        $material = Material::findOrFail($id);

        unset($data['id'], $data['material_code'], $data['current_version'], $data['status']);

        $material->update($data);

        return $this->find($material->id);
    }

    /**
     * Append a new immutable version and make it current (material.md §8, BR004/BR005).
     */
    public function addVersion($id, array $data): MaterialVersion
    {
        return DB::transaction(function () use ($id, $data) {
            $material = Material::findOrFail($id);

            $version = MaterialVersion::create([
                'material_id' => $material->id,
                'version' => $material->current_version + 1,
                'file_id' => $data['file_id'] ?? null,
                'file_name' => $data['file_name'] ?? null,
                'file_size' => $data['file_size'] ?? null,
                'mime_type' => $data['mime_type'] ?? null,
                'change_log' => $data['change_log'] ?? null,
                'created_by' => Auth::guard('api')->id() ?? Auth::id(),
                'created_at' => now(),
            ]);

            $material->update(['current_version' => $version->version]);

            return $version;
        });
    }

    /**
     * Roll the live pointer back to an existing version (material.md §8, BR006).
     *
     * @throws \RuntimeException
     */
    public function rollbackVersion($id, int $version): Material
    {
        $material = Material::findOrFail($id);

        $exists = MaterialVersion::where('material_id', $material->id)->where('version', $version)->exists();
        if (! $exists) {
            throw new \RuntimeException('Phiên bản tài liệu không tồn tại.');
        }

        $material->update(['current_version' => $version]);

        return $this->find($material->id);
    }

    /**
     * Publish a draft into the active library (material.md §16).
     *
     * @throws \RuntimeException
     */
    public function publish($id): Material
    {
        $material = Material::findOrFail($id);

        if ($material->current_version < 1) {
            throw new \RuntimeException('Tài liệu phải có ít nhất 1 phiên bản trước khi xuất bản.');
        }

        $material->update(['status' => Material::STATUS_ACTIVE]);

        return $this->find($material->id);
    }

    public function delete($id): void
    {
        Material::findOrFail($id)->delete();
    }

    // ── Linking (material.md §9) ─────────────────────────────────────────────────

    /**
     * Link a material to a business entity. Idempotent per (material, entity).
     *
     * @throws \RuntimeException
     */
    public function attach($materialId, array $data): MaterialMapping
    {
        $material = Material::findOrFail($materialId);
        $type = MaterialEntityType::from($data['entity_type']);
        $entityId = (int) $data['entity_id'];

        // Verify the target exists when its table is present in the schema.
        if (Schema::hasTable($type->table())) {
            $exists = DB::table($type->table())->where('id', $entityId)->exists();
            if (! $exists) {
                throw new \RuntimeException('Đối tượng liên kết không tồn tại.');
            }
        }

        // BR007: a material may be linked in many places, but not twice to the same one.
        return MaterialMapping::firstOrCreate([
            'material_id' => $material->id,
            'entity_type' => $type->value,
            'entity_id' => $entityId,
        ]);
    }

    public function detach($mappingId): void
    {
        MaterialMapping::findOrFail($mappingId)->delete();
    }

    /**
     * Where this material is used, grouped by entity type (material.md §11).
     *
     * @return Collection<int, MaterialMapping>
     */
    public function mappings($materialId)
    {
        Material::findOrFail($materialId);

        return MaterialMapping::where('material_id', $materialId)->orderBy('entity_type')->get();
    }
}
