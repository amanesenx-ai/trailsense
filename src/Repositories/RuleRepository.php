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
            "SELECT code, factor, metric, comparator, threshold, level, name, precaution
             FROM risk_rule WHERE active
             ORDER BY array_position(ARRAY['rain','wind','heat','cold','daylight']::varchar[], factor), level"
        )->fetchAll();
    }
}
