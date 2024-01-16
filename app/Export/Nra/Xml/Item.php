<?php
namespace Woo_BG\Export\Nra\Xml;

class Item {
    private string $name;

    private float $quantity;

    public float $subPrice;

    private float $price;

    private int $vatRate;

    private bool $addVat;

    public function __construct(
        string $name,
        float $quantity,
        float $subPrice,
        float $price,
        int $vatRate = 20,
        bool $addVat = true
    ) {
        $this->name = $name;
        $this->quantity = $quantity;
        $this->subPrice = $subPrice;
        $this->price = $price;
        $this->vatRate = $vatRate;
        $this->addVat = $addVat;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return float
     */
    public function getQuantity(): float
    {
        return $this->quantity;
    }

    /**
     * @return float
     */
    public function getPrice(): float
    {
        return $this->price;
    }

    /**
     * @return float
     */
    public function getSubPrice(): float
    {
        $sub_price = $this->subPrice;
        
        if ( $this->needToRemoveVat( $this->subPrice, $this->getFinalSubPrice() ) ) {
            $sub_price -= woo_bg_calculate_vat_from_price( $sub_price, $this->getVatRate() );
        }

        return $sub_price;
    }

    /**
     * @return int
     */
    public function getVatRate(): int
    {
        return $this->vatRate;
    }

    public function getVat(): float
    {
        $vat = ( $this->vatRate * $this->price / 100 ) * $this->quantity;

        if ( $this->needToRemoveVat( $this->price, $this->getFinalprice() ) ) {
            $vat = woo_bg_calculate_vat_from_price( $this->price, $this->getVatRate() ) * $this->quantity;
        }

        return $vat;
    }

    public function getSubVat(): float
    {
        $sub_vat = ( $this->vatRate * $this->subPrice / 100 ) * $this->quantity;

        if ( $this->needToRemoveVat( $this->subPrice, $this->getFinalSubPrice() ) ) {
            $sub_vat = woo_bg_calculate_vat_from_price( $this->subPrice, $this->getVatRate() ) * $this->quantity;
        }

        return $sub_vat;
    }

    public function getFinalPrice(): float
    {
        $single_price = $this->price;

        if ( $this->addVat ) {
            $single_price = $this->price * (1+($this->vatRate / 100));
        }

        return $single_price * $this->quantity;
    }

    public function getFinalSubPrice(): float
    {
        $single_price = $this->subPrice;

        if ( $this->addVat ) {
            $single_price = $this->subPrice * (1+($this->vatRate / 100));
        }
        
        return $single_price * $this->quantity;
    }

    public function needToRemoveVat( $single_price, $total ) {
        return ( 
            $this->getVatRate() != 0 &&
            number_format( $single_price * $this->getQuantity(), 2, '.', '') ===  number_format( $total, 2, '.', '') 
        );
    }
}