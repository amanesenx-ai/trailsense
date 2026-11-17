<?php
declare(strict_types=1);

namespace TrailSense\Repositories;

use PDO;

final class RuleRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    /** Active rules, grouped in the order users read them. */
    public function active(): array
    {
        return $this->db->query(
            "SELECT code, factor, metric, comparator, threshold, level, name, precaution, required
             FROM risk_rule WHERE active
             ORDER BY array_position(ARRAY['official','rain','wind','heat','cold','daylight','fire','trail']::varchar[], factor), level"
        )->fetchAll();
    }
}
