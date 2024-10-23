<?php
namespace Woo_BG;

defined( 'ABSPATH' ) || exit;

use \SplFileObject as File;

/**
 * Enhanced CSV file object
 */
class CsvFile extends File implements \Countable {
	const DEFAULT_ENCODING = 'utf-8';
	private $encoding = 'utf-8';
	private $is_head_row = false;
	/**
	 * Current row.
	 */
	private $row_counter = 0;
	private $column_names;
	private $uses_column_names = false;
	private $offset_row = 0;
	private $start_column = 0;
	private $columns_to_skip = array();

	function __construct($file, $delimiter = ',', $enclosure = '"', $escape = "\\", $flags = null) {
		if (!file_exists($file)) {
			throw new Exception("File $file does not exist. ");
		}

		if (filesize($file) === 0) {
			throw new Exception("Empty file. ");
		}

		if ( is_null( $flags ) ) {
			$flags = File::READ_CSV | File::READ_AHEAD | File::SKIP_EMPTY | File::DROP_NEW_LINE;
		}

		parent::__construct($file, 'r+');
		$this->setFlags($flags);
		$this->setCsvControl($delimiter, $enclosure, $escape);
	}

	/**
	 * Read number of lines in CSV
	 * @return int number of lines
	 */
	function count(): int {
		return count($this->to_array());
	}

	function set_encoding($encoding) {
		$this->encoding = $encoding;
	}

	public function to_array() {
		$rows = [];
		foreach ($this as $row) {
			$rows[] = $row;
		}

		return $rows;
	}

	public function rewind(): void {
		$this->seek($this->offset_row);
	}

	/**
	 * Override the key function in order to allow shifting in indecies according
	 * to the current offset.
	 */
	public function key(): int {
		return $this->row_counter - 1;
	}

	public function current(): array|string|false {
		$this->row_counter++;
		$row = parent::current();

		$row_keys = array_keys($row);
		if (!in_array($this->start_column, $row_keys)) {
			throw new Exception(sprintf('Start column must be between %d and %d.', min($row_keys), max($row_keys)));
		}

		$formatted_row = $this->format_row($row);

		return $formatted_row;
	}

	private function remove_columns($old_row) {
		$new_row = array();

		$index = 0;
		foreach ($old_row as $column_name => $column_value) {
			if (!in_array($index, $this->columns_to_skip)) {
				$new_row[$column_name] = $column_value;
			}

			$index++;
		}

		return $new_row;
	}

	private function convert_to_default_encoding($val) {
		return mb_convert_encoding($val, static::DEFAULT_ENCODING, $this->encoding);
	}

	private function format_row($row) {
		if ($this->encoding !== static::DEFAULT_ENCODING) {
			$row = array_map([$this, 'convert_to_default_encoding'], $row);
		}
		$cols = $this->get_column_names($row);
		if (count($cols) !== count($row)) {
			$row = array_intersect_key($row, $cols);
		}
		$row = array_combine(
			$cols,
			$row
		);

		// don't remove columns from the head row
		// we remove columns after the row is combined with the header columns
		if (!$this->is_head_row) {
			$row = $this->remove_columns($row);
		}

		if (!$this->uses_column_names) {
			$row = array_values($row);
		}

		return $row;
	}

	private function get_column_names($row) {
		if (!empty($this->column_names)) {
			return $this->column_names;
		}

		return array_keys($row);
	}

	public function set_column_names($mapping) {
		$this->uses_column_names = true;

		if (empty($this->column_names)) {
			$this->column_names = $mapping;
		} else {
			$mapping_indecies = array_flip($this->column_names);

			// User code wants to map part of the columns
			if (count($mapping_indecies) !== count($mapping)) {
				$mapping_indecies = array_intersect_key($mapping_indecies, $mapping);
			}

			// some of the columns that the user code wants to map are not found
			if (count($mapping_indecies) !== count($mapping)) {
				$bad_cols = array_diff_key($mapping, $mapping_indecies);
				throw new Exception("The following column(s) are not present in the source file: " . implode(', ', $bad_cols));
			}

			$this->column_names = array_combine(
				$mapping_indecies,
				$mapping
			);
		}
	}

	public function use_first_row_as_header() {
		if ($this->row_counter !== 0) {
			throw new \LogicException("Column mapping can't be changed after CSV processing has been started");
		}

		$this->uses_column_names = true;

		$this->is_head_row = true;
		$this->column_names = $this->current();
		$this->is_head_row = false;

		// Start processing from the second row(since the first one isn't part of the data)
		$this->offset_row++;
		$this->rewind();
	}

	public function skip_to_row($row) {
		$this->offset_row = $row;
		$this->rewind();
	}

	public function skip_columns($indexes) {
		$this->set_columns_to_skip($indexes);
	}

	public function skip_to_column($column_index) {
		if (!is_int($column_index)) {
			throw new Exception('Only numbers are allowed for skip to column.');
		}

		if ($column_index < 0) {
			throw new Exception('Please use numbers larger than zero.');
		}

		$this->start_column = $column_index;

		// this is to handle the strange case, when the user wants to start from the first column (which happens by default)
		if ($column_index === 0) {
			$last_column_index = 0;
		} else {
			$last_column_index = $column_index - 1;
		}

		$this->set_columns_to_skip(range(0, $last_column_index));
	}

	private function set_columns_to_skip($columns) {
		$this->columns_to_skip = array_unique(array_merge($columns, $this->columns_to_skip));
	}
}
