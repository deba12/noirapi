<?php
/** @noinspection UnknownInspectionInspection */
/** @noinspection PhpUnused */
declare(strict_types = 1);

namespace noirapi;

use DateTime;
use Exception;
use GUMP;
use Mirovit\EIKValidator\EIKValidator;
use ReCaptcha\ReCaptcha;
use RuntimeException;

class Validate extends GUMP {

	/**
	 * Validate constructor.
	 * @param string $lang
	 * @throws Exception
	 */
	public function __construct($lang = 'en') {

		parent::__construct($lang);

		$file = __DIR__ . '/Validate/' . $lang . '.php';
		if(file_exists($file)) {
			/** @noinspection PhpIncludeInspection */
			parent::$validation_methods_errors = require($file);
		}

		//parent::$validation_methods_errors['mismatch'] = 'Can\'t validate input parameters';

	}

	/**
	 * @param $field
	 * @param $input
	 * @param null $param
	 */
	public function validate_optional($field, $input, $param = null): void {

	}

	public function getError(bool $text = true ) {

		if($text) {
			$array = $this->get_errors_array();
			return array_shift($array);
		}

		return array_slice($this->get_errors_array(), 0 , 1);

	}
	/**
	 * @param $field
	 * @param array $input
	 * @param array $param
	 * @param $value
	 * @return bool
	 * @noinspection PhpUnusedParameterInspection
	 */
	protected function validate_captcha($field, array $input, array $param, $value): bool {

		if(isset($_SESSION['captcha']['code']) && strtolower($value) === strtolower($_SESSION['captcha']['code'])) {
			return true;
		}

		if(isset($_SESSION['captcha']['code'])) {
			unset($_SESSION['captcha']['code']);
		}

		return false;

	}

	/**
	 * @param $field
	 * @param array $input
	 * @param array $param
	 * @param $value
	 * @return bool
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function validate_bgmobile($field, array $input, array $param, $value): bool {

		$prefix = substr($value, 0, 2);

		return ($prefix === '08' || $prefix === '09') && strlen($value) === 10;

	}

	/**
	 * @param $field
	 * @param array $input
	 * @param array $param
	 * @param $value
	 * @return bool
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function validate_bulstat($field, array $input, array $param, $value): bool {

		if(class_exists(EIKValidator::class)) {

			try {
				$result = (new EIKValidator())->isValid($value);
			} catch (Exception $exception) {
				$result = false;
			}

			return $result;

		}

		throw new RuntimeException('Unable to find EIKValidator validator class');

	}

	/**
	 * @param $field
	 * @param array $input
	 * @param array $param
	 * @param $value
	 * @return bool
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function validate_g_recaptcha($field, array $input, array $param, $value): bool {

		$resp = (new ReCaptcha(RECAPTCHA_SECRET))->verify($value, $param[0]);

		if($resp->isSuccess()) {
			return true;
		}

		return false;

	}

	/**
	 * @param $field
	 * @param $input
	 * @param float $param
	 * @return bool
	 */
	public function validate_g_recaptcha_v3($field, $input, float $param = 0.5): bool {

		if(class_exists(ReCaptcha::class) && defined('RECAPTCHA_SECRET')) {

			$resp = (new ReCaptcha(RECAPTCHA_SECRET))
				->setExpectedHostname(BASE_DOMAIN)
				->setScoreThreshold($param)
				->verify($input[$field], $_SERVER['REMOTE_ADDR']);

			if($resp->isSuccess()) {
				return true;
			}

			return false;

		}

		throw new RuntimeException('Unable to find ReCaptcha class');

	}

	/**
	 * @param $value
	 * @param $param
	 * @return string|null
	 */
	public function filter_tosqldate($value, $param): ?string {

		$date = DateTime::createFromFormat($param[0], $value)->format('Y-m-d');

		if(is_string($date)) {
			return $date;
		}

		return null;

	}

	/**
	 * @param $value
	 * @return string
	 */
	public function filter_htmldecode($value): string {
		return html_entity_decode($value);
	}

	/**
	 * @param $value
	 * @return string
	 */
	public function filter_urldecode($value): string {
		return urldecode($value);
	}

	/**
	 * @param $value
	 * @return string
	 */
	public function filter_bulstat($value): string {
		return str_replace('BG', '', $value);
	}

}
