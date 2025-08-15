<?php

namespace App\Service;

use App\Dto\ProductDto;
use DateTime;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;

class ProductService
{
    public function __construct(private readonly EntityManagerInterface $em)
    {

    }

    public function save(ProductDto $dto): bool
    {
        $conn = $this->em->getConnection();

        $sql = '
            INSERT INTO tblProductData 
                (strProductCode, strProductName, strProductDesc, intStock, decPrice, dtmDiscontinued, dtmAdded)
            VALUES 
                (:code, :name, :desc, :stock, :price, :discontinued, NOW())
            ON DUPLICATE KEY UPDATE
                strProductName = VALUES(strProductName),
                strProductDesc = VALUES(strProductDesc),
                intStock = VALUES(intStock),
                decPrice = VALUES(decPrice),
                dtmDiscontinued = VALUES(dtmDiscontinued)
        ';

        $params = [
            'code' => $dto->code,
            'name' => $dto->name,
            'desc' => $dto->description,
            'stock' => $dto->stock ?? 0,
            'price' => $dto->price ?? 0,
            'discontinued' => $dto->discontinued ? (new DateTime())->format('Y-m-d H:i:s') : null,
        ];

        try {
            $conn->executeStatement($sql, $params);

            // 1 = insert, 2 = update (ON DUPLICATE KEY UPDATE)
            $rowCount = $conn->executeQuery('SELECT ROW_COUNT()')->fetchOne();

            return $rowCount === 2;
        } catch (Exception $e) {
            $this->logError($dto->code, $e->getMessage());
        }

        return false;
    }
}