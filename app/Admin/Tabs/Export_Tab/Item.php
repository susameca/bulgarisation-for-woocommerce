<?php
namespace Woo_BG\Admin\Tabs\Export_Tab;

class Item extends \Audit\Item {
    public string $name;

    public float $quantity;

    public float $price;

    public int $vatRate;

    public float $subPrice;

    public function __construct(
        string $name,
        float $quantity,
        float $subPrice,
        float $price,
        int $vatRate = 20
    ) {
        parent::__construct( $name, $quantity, $price, $vatRate );
    	$this->name = $name;
        $this->quantity = $quantity;
        $this->price = $price;
        $this->vatRate = $vatRate; 
    	$this->subPrice = $subPrice;
    }

    /**
     * @return float
     */
    public function getSubPrice(): float
    {
        return $this->subPrice;
    }

    public function getSubVat(): float
    {
        return ($this->vatRate * $this->subPrice / 100) * $this->quantity;
    }

    public function getFinalSubPrice(): float
    {
        return ($this->subPrice * (1+($this->vatRate / 100))) * $this->quantity;
    }
}