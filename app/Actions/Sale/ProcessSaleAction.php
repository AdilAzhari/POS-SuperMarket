<?php

declare(strict_types=1);

namespace App\Actions\Sale;

use App\DTOs\CreateSaleDTO;
use App\DTOs\SaleResponseDTO;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\SaleProcessingException;
use App\Services\SaleService;
use Exception;

final readonly class ProcessSaleAction
{
    public function __construct(
        private SaleService $saleService
    ) {}

    /**
     * @throws InsufficientStockException
     * @throws SaleProcessingException
     */
    public function execute(CreateSaleDTO $saleDTO): SaleResponseDTO
    {
        try {
            return $this->saleService->createSale($saleDTO);
        } catch (SaleProcessingException $e) {
            throw $e;
        } catch (Exception $e) {
            if (str_contains($e->getMessage(), 'Insufficient stock')) {
                throw new InsufficientStockException($e->getMessage(), 422, $e);
            }
            throw new SaleProcessingException('Failed to process sale: '.$e->getMessage(), 500, $e);
        }
    }
}
