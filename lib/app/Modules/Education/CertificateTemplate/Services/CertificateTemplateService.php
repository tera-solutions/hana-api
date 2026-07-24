<?php

namespace App\Modules\Education\CertificateTemplate\Services;

use App\Modules\Education\CertificateTemplate\Models\CertificateTemplate;
use Package\Database\Concerns\HandlesEntityQueries;

class CertificateTemplateService
{
    use HandlesEntityQueries;

    public function paginate(array $params = [])
    {
        $query = CertificateTemplate::query();

        if (! empty($params['search'])) {
            $query->where('name', 'like', '%'.$params['search'].'%');
        }

        if (! empty($params['status'])) {
            $query->where('status', $params['status']);
        }

        $this->applySort($query, $params, ['name', 'status', 'created_at']);

        return $query->paginate($this->resolvePerPage($params));
    }

    public function find($id): CertificateTemplate
    {
        return CertificateTemplate::findOrFail($id);
    }

    public function create(array $data): CertificateTemplate
    {
        $template = new CertificateTemplate($data);
        $template->status = $data['status'] ?? CertificateTemplate::STATUS_ACTIVE;
        $template->save();

        return $this->find($template->id);
    }

    public function update($id, array $data): CertificateTemplate
    {
        $template = $this->find($id);

        unset($data['id']);

        $template->update($data);

        return $this->find($template->id);
    }

    /**
     * @throws \RuntimeException
     */
    public function suspend($id): CertificateTemplate
    {
        $template = $this->find($id);

        if ($template->status === CertificateTemplate::STATUS_INACTIVE) {
            throw new \RuntimeException('Mẫu chứng nhận đang ở trạng thái ngừng sử dụng.');
        }

        $template->update(['status' => CertificateTemplate::STATUS_INACTIVE]);

        return $this->find($template->id);
    }

    /**
     * @throws \RuntimeException
     */
    public function restore($id): CertificateTemplate
    {
        $template = $this->find($id);

        if ($template->status === CertificateTemplate::STATUS_ACTIVE) {
            throw new \RuntimeException('Mẫu chứng nhận đang được sử dụng.');
        }

        $template->update(['status' => CertificateTemplate::STATUS_ACTIVE]);

        return $this->find($template->id);
    }
}
