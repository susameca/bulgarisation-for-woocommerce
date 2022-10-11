<?php
namespace Woo_BG\Admin\Invoice;
use tFPDF;

defined( 'ABSPATH' ) || exit;

class Printer extends tFPDF {
	public const ICONV_CHARSET_INPUT = 'UTF-8';
	public const ICONV_CHARSET_OUTPUT_A = 'windows-1251';

	public $angle = 0;
	public $font = 'OpenSans';                 /* Font Name : See inc/fpdf/font for all supported fonts */
	public $columnOpacity = 0.06;               /* Items table background color opacity. Range (0.00 - 1) */
	public $columnSpacing = 0.3;                /* Spacing between Item Tables */
	public $referenceformat = ['.', ',', 'left', false, false];    /* Currency formater */
	public $margins = [
		'l' => 15,
		't' => 15,
		'r' => 15,
	]; /* l: Left Side , t: Top Side , r: Right Side */
	public $fontSizeProductDescription = 7;                /* font size of product description */

	public $document;
	public $type;
	public $reference;
	public $logo;
	public $color;
	public $badgeColor;
	public $date;
	public $time;
	public $due;
	public $from;
	public $to;
	public $items;
	public $totals;
	public $badge;
	public $addText;
	public $address;
	public $order_number;
	public $originalInvoiceNumber;
	public $receivedBy;
	public $compiledBy;
	public $footernote;
	public $dimensions;
	public $paymentType;
	public $transaction_id;
	public $display_tofrom = true;
	public $customHeaders = [];
	protected $displayToFromHeaders = true;
	protected $columns;

	public function __construct($size = 'A4', $currency = '$') {
		$this->items = [];
		$this->totals = [];
		$this->addText = [];
		$this->firstColumnWidth = 70;
		$this->currency = $currency;
		$this->maxImageDimensions = [230, 130];
		$this->dimensions         = [61.0, 34.0];
		$this->from               = [''];
		$this->to                 = [''];
		$this->setDocumentSize($size);
		$this->setColor('#222222');

		$this->recalculateColumns();

		parent::__construct('P', 'mm', [$this->document['w'], $this->document['h']]);
		$this->fontpath = __DIR__ . '/font/';

		$this->AliasNbPages();
		$this->SetMargins($this->margins['l'], $this->margins['t'], $this->margins['r']);
	}

	private function setDocumentSize( $dsize ) {
		switch ($dsize) {
			case 'A4':
				$document['w'] = 210;
				$document['h'] = 297;
				break;
			case 'letter':
				$document['w'] = 215.9;
				$document['h'] = 279.4;
				break;
			case 'legal':
				$document['w'] = 215.9;
				$document['h'] = 355.6;
				break;
			default:
				$document['w'] = 210;
				$document['h'] = 297;
				break;
		}

		$this->document = $document;
	}

	private function resizeToFit($image) {
		list($width, $height) = getimagesize($image);
		$newWidth = $this->maxImageDimensions[0] / $width;
		$newHeight = $this->maxImageDimensions[1] / $height;
		$scale = min($newWidth, $newHeight);

		return [
			round($this->pixelsToMM($scale * $width)),
			round($this->pixelsToMM($scale * $height)),
		];
	}

	private function pixelsToMM($val) {
		$mm_inch = 25.4;
		$dpi = 96;

		return ($val * $mm_inch) / $dpi;
	}

	private function hex2rgb($hex) {
		$hex = str_replace('#', '', $hex);
		if (strlen($hex) == 3) {
			$r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
			$g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
			$b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
		} else {
			$r = hexdec(substr($hex, 0, 2));
			$g = hexdec(substr($hex, 2, 2));
			$b = hexdec(substr($hex, 4, 2));
		}
		$rgb = [$r, $g, $b];

		return $rgb;
	}

	private function br2nl($string) {
		return preg_replace('/\<br(\s*)?\/?\>/i', "\n", $string);
	}

	public function isValidTimezoneId($zone) {
		try {
			new \DateTimeZone($zone);
		} catch (Exception $e) {
			return false;
		}

		return true;
	}

	public function setTimeZone($zone = '') {
		if (!empty($zone) and $this->isValidTimezoneId($zone) === true) {
			date_default_timezone_set($zone);
		}
	}

	public function setType($title) {
		$this->title = $title;
	}

	public function setColor($rgbcolor) {
		$this->color = $this->hex2rgb($rgbcolor);
	}

	public function setDate($date) {
		$this->date = $date;
	}

	public function setPaymentType($paymentType) {
		$this->paymentType = $paymentType;
	}

	public function setTransactionId($transaction_id) {
		$this->transaction_id = $transaction_id;
	}

	public function setReceivedBy($receivedBy) {
		$this->receivedBy = $receivedBy;
	}

	public function setCompiledBy($compiledBy) {
		$this->compiledBy = $compiledBy;
	}

	public function setAddress($address) {
		$this->address = $address;
	}

	public function setOrderNumber($order_number) {
		$this->order_number = $order_number;
	}

	public function setOriginalInvoiceNumber($originalInvoiceNumber) {
		$this->originalInvoiceNumber = $originalInvoiceNumber;
	}

	public function setTime($time) {
		$this->time = $time;
	}

	public function setDue($date) {
		$this->due = $date;
	}

	public function setLogo($logo = 0, $maxWidth = 0, $maxHeight = 0) {
		if ($maxWidth and $maxHeight) {
			$this->maxImageDimensions = [$maxWidth, $maxHeight];
		}
		$this->logo = $logo;
		$this->dimensions = $this->resizeToFit($logo);
	}

	public function hide_tofrom() {
		$this->display_tofrom = false;
	}

	public function hideToFromHeaders() {
		$this->displayToFromHeaders = false;
	}

	public function setFrom($data) {
		$this->from = $data;
	}

	public function setTo($data) {
		$this->to = $data;
	}

	public function setReference($reference) {
		$this->reference = $reference;
	}

	public function setNumberFormat($decimals = '.', $thousands_sep = ',', $alignment = 'left', $space = true, $negativeParenthesis = false) {
		$this->referenceformat = [$decimals, $thousands_sep, $alignment, $space, $negativeParenthesis];
	}

	public function setFontSizeProductDescription($data) {
		$this->fontSizeProductDescription = $data;
	}

	public function flipflop() {
		$this->flipflop = true;
	}

	public function price($price) {
		$decimalPoint = $this->referenceformat[0];
		$thousandSeparator = $this->referenceformat[1];
		$alignment = isset($this->referenceformat[2]) ? strtolower($this->referenceformat[2]) : 'left';
		$spaceBetweenCurrencyAndAmount = isset($this->referenceformat[3]) ? (bool) $this->referenceformat[3] : true;
		$space = $spaceBetweenCurrencyAndAmount ? ' ' : '';
		$negativeParenthesis = isset($this->referenceformat[4]) ? (bool) $this->referenceformat[4] : false;

		$number = number_format($price, 2, $decimalPoint, $thousandSeparator);
		if ($negativeParenthesis && $price < 0) {
			$number = substr($number, 1);
			if ('right' == $alignment) {
				return '(' . $number . $space . $this->currency . ')';
			} else {
				return '(' . $this->currency . $space . $number . ')';
			}
		} else {
			if ('right' == $alignment) {
				return $number . $space . $this->currency;
			} else {
				return $this->currency . $space . $number;
			}
		}
	}

	public function addCustomHeader($title, $content) {
		$this->customHeaders[] = [
			'title' => $title,
			'content' => $content,
		];
	}

	public function addItem($item, $description, $quantity, $vat, $price, $discount, $total) {
		$p['item'] = $item;
		$p['description'] = $this->br2nl($description);

		if ($vat !== false) {
			$p['vat'] = $vat;
			if (is_numeric($vat)) {
				$p['vat'] = $this->price($vat);
			}
			$this->vatField = true;
			$this->recalculateColumns();
		}
		$p['quantity'] = $quantity;
		$p['price'] = $price;
		$p['total'] = $total;

		if ($discount !== false) {
			$this->firstColumnWidth = 58;
			$p['discount'] = $discount;
			if (is_numeric($discount)) {
				$p['discount'] = $this->price($discount);
			}
			$this->discountField = true;
			$this->recalculateColumns();
		}
		$this->items[] = $p;
	}

	public function addTotal($name, $value, $colored = false) {
		$t['name'] = $name;
		$t['value'] = $value;
		if (is_numeric($value)) {
			$t['value'] = $this->price($value);
		}
		$t['colored'] = $colored;
		$this->totals[] = $t;
	}

	public function addTitle($title) {
		$this->addText[] = ['title', $title];
	}

	public function addParagraph($paragraph) {
		$paragraph = $this->br2nl($paragraph);
		$this->addText[] = ['paragraph', $paragraph];
	}

	public function addBadge($badge, $color = false) {
		$this->badge = $badge;

		if ($color) {
			$this->badgeColor = $this->hex2rgb($color);
		} else {
			$this->badgeColor = $this->color;
		}
	}

	public function setFooternote($note) {
		$this->footernote = $note;
	}

	public function render($name = '', $destination = '') {
		$this->AddPage();
		$this->Body();
		$this->AliasNbPages();

		return $this->Output($destination, $name);
	}

	public function Header() {
		if (isset($this->logo) and !empty($this->logo)) {
			$this->Image(
				$this->logo,
				$this->margins['l'],
				$this->margins['t'],
				$this->dimensions[0],
				$this->dimensions[1]
			);
		}

		$this->AddFont('OpenSans','', 'OpenSans-Regular.ttf', true);
		$this->AddFont('OpenSans','B', 'OpenSans-Bold.ttf', true);
		$this->font = 'OpenSans';

		//Title
		$this->SetTextColor(0, 0, 0);
		$this->SetFont($this->font, 'B', 20);
		if (isset($this->title) and !empty($this->title)) {
			$this->Cell(0, 5, $this->title, 0, 1, 'R');
		}
		$this->SetFont($this->font, '', 9);
		$this->Ln(5);

		$lineheight = 5;
		//Calculate position of strings
		$this->SetFont($this->font, 'B', 9);

		$positionX = ($this->document['w'] - $this->margins['l'] - $this->margins['r']) / 2;

		// Original invoice number
		if ( !empty( $this->originalInvoiceNumber ) ) {
			$this->Cell($positionX, $lineheight);
			$this->SetTextColor($this->color[0], $this->color[1], $this->color[2]);
			$this->Cell(
				32,
				$lineheight,
				mb_strtoupper(__('To invoice №', 'woo-bg') ) . ':',
				0,
				0,
				'L'
			);
			$this->SetTextColor(50, 50, 50);
			$this->SetFont($this->font, '', 9);
			$this->Cell(0, $lineheight, $this->originalInvoiceNumber, 0, 1, 'R');
		}

		//Number
		if (!empty($this->reference)) {
			$this->Cell($positionX, $lineheight);
			$this->SetTextColor($this->color[0], $this->color[1], $this->color[2]);
			$this->Cell(
				32,
				$lineheight,
				mb_strtoupper('№') . ':',
				0,
				0,
				'L'
			);
			$this->SetTextColor(50, 50, 50);
			$this->SetFont($this->font, '', 9);
			$this->Cell(0, $lineheight, $this->reference, 0, 1, 'R');
		}
		//Date
		$this->Cell($positionX, $lineheight);
		$this->SetFont($this->font, 'B', 9);
		$this->SetTextColor($this->color[0], $this->color[1], $this->color[2]);
		$this->Cell(32, $lineheight, mb_strtoupper(__( 'Order date', 'woo-bg' )) . ':', 0, 0, 'L');
		$this->SetTextColor(50, 50, 50);
		$this->SetFont($this->font, '', 9);
		$this->Cell(0, $lineheight, $this->date, 0, 1, 'R');

		//Time
		if (!empty($this->time)) {
			$this->Cell($positionX, $lineheight);
			$this->SetFont($this->font, 'B', 9);
			$this->SetTextColor($this->color[0], $this->color[1], $this->color[2]);
			$this->Cell(
				32,
				$lineheight,
				mb_strtoupper(__( 'Date of tax. event', 'woo-bg' )) . ':',
				0,
				0,
				'L'
			);
			$this->SetTextColor(50, 50, 50);
			$this->SetFont($this->font, '', 9);
			$this->Cell(0, $lineheight, $this->time, 0, 1, 'R');
		}
		//Due date
		if (!empty($this->due)) {
			$this->Cell($positionX, $lineheight);
			$this->SetFont($this->font, 'B', 9);
			$this->SetTextColor($this->color[0], $this->color[1], $this->color[2]);
			$this->Cell(32, $lineheight, mb_strtoupper(__( 'Due date', 'woo-bg' )) . ':', 0, 0, 'L');
			$this->SetTextColor(50, 50, 50);
			$this->SetFont($this->font, '', 9);
			$this->Cell(0, $lineheight, $this->due, 0, 1, 'R');
		}
		//address
		if (!empty($this->address)) {
			$this->Cell($positionX, $lineheight);
			$this->SetFont($this->font, 'B', 9);
			$this->SetTextColor($this->color[0], $this->color[1], $this->color[2]);
			$this->Cell(32, $lineheight, mb_strtoupper(__( 'Place of transaction', 'woo-bg' )) . ':', 0, 0, 'L');
			$this->SetTextColor(50, 50, 50);
			$this->SetFont($this->font, '', 9);
			$this->Cell(0, $lineheight, $this->address, 0, 1, 'R');
		}
		//order number
		if (!empty($this->order_number)) {
			$this->Cell($positionX, $lineheight);
			$this->SetFont($this->font, 'B', 9);
			$this->SetTextColor($this->color[0], $this->color[1], $this->color[2]);
			$this->Cell(32, $lineheight, mb_strtoupper(__( 'Order Number', 'woo-bg' )) . ':', 0, 0, 'L');
			$this->SetTextColor(50, 50, 50);
			$this->SetFont($this->font, '', 9);
			$this->Cell(0, $lineheight, $this->order_number, 0, 1, 'R');
		}
		//Custom Headers
		if (count($this->customHeaders) > 0) {
			foreach ($this->customHeaders as $customHeader) {
				$this->Cell($positionX, $lineheight);
				$this->SetFont($this->font, 'B', 9);
				$this->SetTextColor($this->color[0], $this->color[1], $this->color[2]);
				$this->Cell(32, $lineheight, mb_strtoupper($customHeader['title']) . ':', 0, 0, 'L');
				$this->SetTextColor(50, 50, 50);
				$this->SetFont($this->font, '', 9);
				$this->Cell(0, $lineheight, $customHeader['content'], 0, 1, 'R');
			}
		}

		//First page
		if ($this->PageNo() == 1) {
			$dimensions = $this->dimensions[1] ?? 0;
			if (($this->margins['t'] + $dimensions) > $this->GetY()) {
				$this->SetY($this->margins['t'] + $dimensions + 5);
			} else {
				$this->SetY($this->GetY() + 10);
			}
			$this->Ln(5);
			$this->SetFillColor($this->color[0], $this->color[1], $this->color[2]);
			$this->SetTextColor($this->color[0], $this->color[1], $this->color[2]);

			$this->SetDrawColor($this->color[0], $this->color[1], $this->color[2]);
			$this->SetFont($this->font, 'B', 10);
			$width = ($this->document['w'] - $this->margins['l'] - $this->margins['r']) / 2;
			if (isset($this->flipflop)) {
				$from = __( 'Billing from', 'woo-bg' );
				$to = __( 'Billing to', 'woo-bg' );
				
				$this->to = $from;
				$this->from = $to;

				$to = $this->to;
				$from = $this->from;
			}

			if ($this->display_tofrom === true) {
				if ($this->displayToFromHeaders === true) {
					$this->Cell($width, $lineheight, mb_strtoupper(__( 'Billing from', 'woo-bg' )), 0, 0, 'L');
					$this->Cell(0, $lineheight, mb_strtoupper(__( 'Billing to', 'woo-bg' )), 0, 0, 'L');
					$this->Ln(7);
					$this->SetLineWidth(0.4);
					$this->Line($this->margins['l'], $this->GetY(), $this->margins['l'] + $width - 10, $this->GetY());
					$this->Line(
						$this->margins['l'] + $width,
						$this->GetY(),
						$this->margins['l'] + $width + $width,
						$this->GetY()
					);
				} else {
					$this->Ln(2);
				}

				//Information
				$this->Ln(5);
				$this->SetTextColor(50, 50, 50);
				$this->SetFont($this->font, 'B', 10);
				$this->Cell($width, $lineheight, $this->from[0] ?? 0, 0, 0, 'L');
				$this->Cell(0, $lineheight, $this->to[0], 0, 0, 'L');
				$this->SetFont($this->font, '', 8);
				$this->SetTextColor(100, 100, 100);
				$this->Ln(7);
				for ($i = 1, $iMax = max($this->from === null ? 0 : count($this->from), $this->to === null ? 0 : count($this->to)); $i < $iMax; $i++) {
					// avoid undefined error if TO and FROM array lengths are different
					if (!empty($this->from[$i]) || !empty($this->to[$i])) {
						$this->Cell($width, $lineheight, empty($this->from[$i]) ? '' : $this->from[$i], 0, 0, 'L');
						$this->Cell(0, $lineheight, empty($this->to[$i]) ? '' : $this->to[$i], 0, 0, 'L');
					}
					$this->Ln(5);
				}
				$this->Ln(-6);
				$this->Ln(5);
			} else {
				$this->Ln(-10);
			}
		}
		//Table header
		if (!isset($this->productsEnded)) {
			$width_other = ($this->document['w'] - $this->margins['l'] - $this->margins['r'] - $this->firstColumnWidth - ($this->columns * $this->columnSpacing)) / ($this->columns - 1);
			$this->SetTextColor(50, 50, 50);
			$this->Ln(12);
			$this->SetFont($this->font, 'B', 9);
			$this->Cell(1, 10, '', 0, 0, 'L', 0);
			$this->Cell(
				$this->firstColumnWidth,
				10,
				mb_strtoupper(__( 'Product', 'woo-bg' )),
				0,
				0,
				'L',
				0
			);
			$this->Cell($this->columnSpacing, 10, '', 0, 0, 'L', 0);
			$this->Cell($width_other, 10, mb_strtoupper(__( 'Qty', 'woo-bg' )), 0, 0, 'C', 0);
			if (isset($this->vatField)) {
				$this->Cell($this->columnSpacing, 10, '', 0, 0, 'L', 0);
				$this->Cell(
					$width_other,
					10,
					mb_strtoupper(__( 'Vat', 'woo-bg' )),
					0,
					0,
					'C',
					0
				);
			}
			$this->Cell($this->columnSpacing, 10, '', 0, 0, 'L', 0);
			$this->Cell($width_other, 10, mb_strtoupper(__( 'Price', 'woo-bg' )), 0, 0, 'C', 0);
			$this->Cell($this->columnSpacing, 10, '', 0, 0, 'L', 0);
			$this->Cell($width_other, 10, mb_strtoupper(__( 'Total', 'woo-bg' )), 0, 0, 'C', 0);
			$this->Ln();
			$this->SetLineWidth(0.3);
			$this->SetDrawColor($this->color[0], $this->color[1], $this->color[2]);
			$this->Line($this->margins['l'], $this->GetY(), $this->document['w'] - $this->margins['r'], $this->GetY());
			$this->Ln(2);
		} else {
			$this->Ln(12);
		}
	}

	public function Body() {
		$width_other = ($this->document['w'] - $this->margins['l'] - $this->margins['r'] - $this->firstColumnWidth - ($this->columns * $this->columnSpacing)) / ($this->columns - 1);
		$cellHeight = 8;
		$bgcolor = (1 - $this->columnOpacity) * 255;
		if ($this->items) {
			foreach ($this->items as $item) {
				if ((empty($item['item'])) || (empty($item['description']))) {
					$this->Ln($this->columnSpacing);
				}
				if ($item['description']) {
					//Precalculate height
					$calculateHeight = new self();
					$calculateHeight->addPage();
					$calculateHeight->setXY(0, 0);
					$calculateHeight->SetFont($this->font, '', 7);
					$calculateHeight->MultiCell(
						$this->firstColumnWidth,
						3,
						$item['description'],
						0,
						'L',
						1
					);
					$descriptionHeight = $calculateHeight->getY() + $cellHeight + 2;
					$pageHeight = $this->document['h'] - $this->GetY() - $this->margins['t'] - $this->margins['t'];
					if ($pageHeight < 35) {
						$this->AddPage();
					}
				}
				$cHeight = $cellHeight;
				$this->SetFont($this->font, 'b', 8);
				$this->SetTextColor(50, 50, 50);
				$this->SetFillColor($bgcolor, $bgcolor, $bgcolor);
				$this->Cell(1, $cHeight, '', 0, 0, 'L', 1);
				$x = $this->GetX();
				$this->Cell(
					$this->firstColumnWidth,
					$cHeight,
					$item['item'],
					0,
					0,
					'L',
					1
				);
				if ($item['description']) {
					$resetX = $this->GetX();
					$resetY = $this->GetY();
					$this->SetTextColor(120, 120, 120);
					$this->SetXY($x, $this->GetY() + 8);
					$this->SetFont($this->font, '', $this->fontSizeProductDescription);
					$this->MultiCell(
						$this->firstColumnWidth,
						floor($this->fontSizeProductDescription / 2),
						$item['description'],
						0,
						'L',
						1
					);
					//Calculate Height
					$newY = $this->GetY();
					$cHeight = $newY - $resetY + 2;
					//Make our spacer cell the same height
					$this->SetXY($x - 1, $resetY);
					$this->Cell(1, $cHeight, '', 0, 0, 'L', 1);
					//Draw empty cell
					$this->SetXY($x, $newY);
					$this->Cell($this->firstColumnWidth, 2, '', 0, 0, 'L', 1);
					$this->SetXY($resetX, $resetY);
				}
				$this->SetTextColor(50, 50, 50);
				$this->SetFont($this->font, '', 8);
				$this->Cell($this->columnSpacing, $cHeight, '', 0, 0, 'L', 0);
				$this->Cell($width_other, $cHeight, $item['quantity'], 0, 0, 'C', 1);
				if (isset($this->vatField)) {
					$this->Cell($this->columnSpacing, $cHeight, '', 0, 0, 'L', 0);
					if (isset($item['vat'])) {
						$this->Cell($width_other, $cHeight, $item['vat'], 0, 0, 'C', 1);
					} else {
						$this->Cell($width_other, $cHeight, '', 0, 0, 'C', 1);
					}
				}
				$this->Cell($this->columnSpacing, $cHeight, '', 0, 0, 'L', 0);
				$this->Cell($width_other, $cHeight, $this->price($item['price']), 0, 0, 'C', 1);
				$this->Cell($this->columnSpacing, $cHeight, '', 0, 0, 'L', 0);
				$this->Cell($width_other, $cHeight, $this->price($item['total']), 0, 0, 'C', 1);
				$this->Ln();
				$this->Ln($this->columnSpacing);
			}
		}
		$badgeX = $this->getX();
		$badgeY = $this->getY();

		//Add totals
		if ($this->totals) {
			foreach ($this->totals as $total) {
				$this->SetTextColor(50, 50, 50);
				$this->SetFillColor($bgcolor, $bgcolor, $bgcolor);
				$this->Cell(1 + $this->firstColumnWidth, $cellHeight, '', 0, 0, 'L', 0);
				for ($i = 0; $i < $this->columns - 3; $i++) {
					$this->Cell($width_other, $cellHeight, '', 0, 0, 'L', 0);
					$this->Cell($this->columnSpacing, $cellHeight, '', 0, 0, 'L', 0);
				}
				$this->Cell($this->columnSpacing, $cellHeight, '', 0, 0, 'L', 0);
				if ($total['colored']) {
					$this->SetTextColor(255, 255, 255);
					$this->SetFillColor($this->color[0], $this->color[1], $this->color[2]);
				}
				$this->SetFont($this->font, 'b', 8);
				$this->Cell(1, $cellHeight, '', 0, 0, 'L', 1);
				$this->Cell(
					$width_other - 1,
					$cellHeight,
					$total['name'],
					0,
					0,
					'L',
					1
				);
				$this->Cell($this->columnSpacing, $cellHeight, '', 0, 0, 'L', 0);
				$this->SetFont($this->font, 'b', 8);
				$this->SetFillColor($bgcolor, $bgcolor, $bgcolor);
				if ($total['colored']) {
					$this->SetTextColor(255, 255, 255);
					$this->SetFillColor($this->color[0], $this->color[1], $this->color[2]);
				}
				$this->Cell($width_other, $cellHeight, $total['value'], 0, 0, 'C', 1);
				$this->Ln();
				$this->Ln($this->columnSpacing);
			}
		}

		$this->productsEnded = true;
		$this->Ln();
		$this->Ln(3);
		$lineheight = 5;
		if ( !empty( $this->paymentType ) ) {
			$payment_width = ($this->document['w'] - $this->margins['l'] - $this->margins['r']) / 2;
			$this->SetTextColor(0, 0, 0);
			$this->SetFont($this->font, '', 10);
			$this->Cell( $payment_width, $lineheight, sprintf( __( 'Payment method: %s', 'woo-bg' ), $this->paymentType ), 0, 1, 'L');

			if ( !empty( $this->transaction_id ) ) {
				$payment_width = ($this->document['w'] - $this->margins['l'] - $this->margins['r']) / 2;
				$this->SetTextColor(0, 0, 0);
				$this->SetFont($this->font, '', 10);
				$this->Cell( $payment_width, $lineheight, sprintf( __( 'Transaction ID: %s', 'woo-bg' ), $this->transaction_id ), 0, 1, 'L');
			}

			$this->Ln(15);
		}

		if ( !empty( $this->compiledBy ) && !empty( $this->receivedBy ) ) {
			$this->Cell( $payment_width, $lineheight, sprintf( __( 'Compiled by: %s', 'woo-bg' ), $this->compiledBy ), 0, 0, 'L');
			$this->Cell( $payment_width, $lineheight, sprintf( __( 'Received: %s', 'woo-bg' ), $this->receivedBy ), 0, 2, 'L');
			$this->Ln(5);
		}

		//Add information
		foreach ($this->addText as $text) {
			if ($text[0] == 'title') {
				$this->SetFont($this->font, 'b', 9);
				$this->SetTextColor(50, 50, 50);
				$this->Cell(0, 10, mb_strtoupper($text[1]), 0, 0, 'L', 0);
				$this->Ln();
				$this->SetLineWidth(0.3);
				$this->SetDrawColor($this->color[0], $this->color[1], $this->color[2]);
				$this->Line(
					$this->margins['l'],
					$this->GetY(),
					$this->document['w'] - $this->margins['r'],
					$this->GetY()
				);
				$this->Ln(4);
			}
			if ($text[0] == 'paragraph') {
				$this->SetTextColor(80, 80, 80);
				$this->SetFont($this->font, '', 8);
				$this->MultiCell(0, 4, $text[1], 0, 'L', 0);
				$this->Ln(4);
			}
		}
	}

	public function Footer() {
		$this->SetY(-$this->margins['t']);
		$this->SetFont($this->font, '', 8);
		$this->SetTextColor(50, 50, 50);
		$this->Cell(0, 10, $this->footernote, 0, 0, 'L' );
		$this->Cell(
			0,
			10,
			__( 'Page', 'woo-bg' ) . ' ' . $this->PageNo() . ' ' . __( 'of', 'woo-bg' ) . ' {nb}',
			0,
			0,
			'R'
		);
	}

	public function Rotate($angle, $x = -1, $y = -1) {
		if ($x == -1) {
			$x = $this->x;
		}
		if ($y == -1) {
			$y = $this->y;
		}
		if ($this->angle != 0) {
			$this->_out('Q');
		}
		$this->angle = $angle;
		if ($angle != 0) {
			$angle *= M_PI / 180;
			$c = cos($angle);
			$s = sin($angle);
			$cx = $x * $this->k;
			$cy = ($this->h - $y) * $this->k;
			$this->_out(sprintf(
				'q %.5F %.5F %.5F %.5F %.2F %.2F cm 1 0 0 1 %.2F %.2F cm',
				$c,
				$s,
				-$s,
				$c,
				$cx,
				$cy,
				-$cx,
				-$cy
			));
		}
	}

	public function _endpage() {
		if ($this->angle != 0) {
			$this->angle = 0;
			$this->_out('Q');
		}
		parent::_endpage();
	}

	private function recalculateColumns() {
		$this->columns = 4;

		if (isset($this->vatField)) {
			$this->columns += 1;
		}
	}
}
