<?php

namespace App\Service;
use PDO;
class CompanyMatcher
{
    private $db;
    private $matches = [];

    public function __construct(\PDO $db) 
    {
        $this->db = $db;
    }

    public function match(string $postcode, string $bedrooms, string $type): array
    {
        $postcodePrefix = strtoupper(substr(trim($postcode), 0, 2));

        // Find company_ids matching the criteria
        $sql = "
            SELECT DISTINCT cms.company_id
            FROM company_matching_settings cms
            JOIN companies c ON c.id = cms.company_id
            WHERE JSON_CONTAINS(cms.postcodes, JSON_QUOTE(:postcode))
              AND JSON_CONTAINS(cms.bedrooms, JSON_QUOTE(:bedrooms))
              AND cms.type = :type
              AND c.active = 1
              AND CAST(c.credits AS UNSIGNED) > 0
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':postcode' => $postcodePrefix, // Corrected: Pass the postcode prefix directly
            ':bedrooms' => $bedrooms,     // Corrected: Pass the bedrooms directly
            ':type'     => $type
        ]);


        $companyIds = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        if (empty($companyIds)) {
            return [];
        }

        // Shuffle and pick up to 3 companies
        shuffle($companyIds);
        $selectedIds = array_slice($companyIds, 0, 3);

        // Deduct a credit from each selected company
        $deductSql = "UPDATE companies SET credits = CAST(credits AS UNSIGNED) - 1 WHERE id = :id";
        $deductStmt = $this->db->prepare($deductSql);
        foreach ($selectedIds as $id) {
            $deductStmt->execute([':id' => $id]);
        }

        // Fetch company data
        $inClause = implode(',', array_fill(0, count($selectedIds), '?'));
        $companySql = "SELECT * FROM companies WHERE id IN ($inClause)";
        $companyStmt = $this->db->prepare($companySql);
        foreach ($selectedIds as $i => $id) {
            $companyStmt->bindValue($i + 1, $id, PDO::PARAM_INT);
        }
        $companyStmt->execute();
        return $companyStmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function pick(int $count)
    {
        
    }

    public function results(): array
    {
        return $this->matches;
    }

    public function deductCredits()
    {
        
    }
}