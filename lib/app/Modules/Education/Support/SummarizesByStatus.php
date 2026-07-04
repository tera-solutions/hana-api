<?php

namespace App\Modules\Education\Support;

use BackedEnum;

/**
 * Builds the `by_status` map for the module summary endpoints: every status of the
 * feature's enum is present (defaulting to 0), with any unexpected status value seen
 * in the data overlaid on top so the counts always sum to the total.
 */
trait SummarizesByStatus
{
    /**
     * @param  iterable<string, int|string>  $counts  status => aggregate (from the DB)
     * @param  array<int, BackedEnum>  $cases  the feature's status enum cases
     * @return array<string, int>
     */
    protected function countsByStatus(iterable $counts, array $cases): array
    {
        $out = array_fill_keys(array_map(static fn (BackedEnum $c) => (string) $c->value, $cases), 0);

        foreach ($counts as $status => $count) {
            $out[$status] = (int) $count;
        }

        return $out;
    }
}
