<?php
namespace Woo_BG\Export\Nra\Xml;

class Order {
    private string $orderUniqueNumber;

    private \DateTime $orderDate;

    private string $documentNumber;

    private \DateTime $documentDate;

    private float $totalDiscount;

    private string $paymentType;

    /**
     * @var Item[]
     */
    private array $items;

    private string $total;
    
    private string $totalVat;
    
    private string $subtotal;

    private ?string $virtualPosNumber;

    private ?string $transactionNumber;

    private ?string $paymentProcessorIdentifier;

    public function __construct(
        string $orderUniqueNumber,
        \DateTime $orderDate,
        string $documentNumber,
        \DateTime $documentDate,
        float $totalDiscount,
        string $paymentType,
        array $items,
        string $total,
        string $totalVat,
        string $subtotal,
        ?string $virtualPosNumber,
        ?string $transactionNumber,
        ?string $paymentProcessorIdentifier
    ) {
        $this->orderUniqueNumber = $orderUniqueNumber;
        $this->orderDate = $orderDate;
        $this->documentNumber = $documentNumber;
        $this->documentDate = $documentDate;
        $this->totalDiscount = $totalDiscount;
        $this->paymentType = $paymentType;
        $this->items = $items;
        $this->total = $total;
        $this->totalVat = $totalVat;
        $this->subtotal = $subtotal;
        $this->virtualPosNumber = $virtualPosNumber;
        $this->transactionNumber = $transactionNumber;
        $this->paymentProcessorIdentifier = $paymentProcessorIdentifier;
    }

    /**
     * @return string
     */
    public function getOrderUniqueNumber(): string
    {
        return $this->orderUniqueNumber;
    }

    /**
     * @return \DateTime
     */
    public function getOrderDate(): \DateTime
    {
        return $this->orderDate;
    }

    /**
     * @return string
     */
    public function getDocumentNumber(): string
    {
        return $this->documentNumber;
    }

    /**
     * @return \DateTime
     */
    public function getDocumentDate(): \DateTime
    {
        return $this->documentDate;
    }

    /**
     * @return float
     */
    public function getTotalDiscount(): float
    {
        return $this->totalDiscount;
    }

    /**
     * @return string
     */
    public function getPaymentType(): string
    {
        return $this->paymentType;
    }

    /**
     * @return array
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @return string|null
     */
    public function getVirtualPosNumber(): ?string
    {
        return $this->virtualPosNumber;
    }

    /**
     * @return string|null
     */
    public function getTransactionNumber(): ?string
    {
        return $this->transactionNumber;
    }

    /**
     * @return string|null
     */
    public function getPaymentProcessorIdentifier(): ?string
    {
        return $this->paymentProcessorIdentifier;
    }

    public function addItem( $item ): void
    {
        $this->items[] = $item;
    }

    public function getTotalWithoutVat()
    {
        return $this->total - $this->totalVat;
    }

    public function getTotal1()
    {
        return $this->subtotal;
    }

    public function getOrderTotalVat()
    {
        return $this->totalVat;
    }

    public function getOrderTotal()
    {
        return $this->total;
    }
}