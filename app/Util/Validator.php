<?php

namespace App\Util;

class Validator implements SharedObject
{
    const AZ = 'abcdefghijklmnopqrstuvwxyz';
    const NUMS = '0123456789';
    const SPECIAL = '`-=[];\',./\\~!@#$%^&*()_+{}:"<>?';
    const HEX = '0123456789abcdef';
    const SPACE = ' ';
    const MIN_PASSWORD_LENGTH = 5;
    const MAX_PASSWORD_LENGTH = 128;
    const MIN_EMAIL_LENGTH = 5;
    const MAX_EMAIL_LENGTH = 128;
    const MIN_NAME_LENGTH = 2;
    const MAX_NAME_LENGTH = 50;
    const MIN_ADDRESS_LENGTH = 3;
    const MAX_ADDRESS_LENGTH = 128;
    const MIN_CITY_LENGTH = 3;
    const MAX_CITY_LENGTH = 128;
    const MIN_ZIP_LENGTH = 1;
    const MAX_ZIP_LENGTH = 6;
    const BITCOIN_ADDRESS_PATTERN_REGEX = "/^[13][a-km-zA-HJ-NP-Z1-9]{25,34}$/";
    const ZIP_PATTERN_REGEX = "/^([0-9]{5})(-[0-9]{4})?$/i";

    const STATES = [
            'Alabama' => 'AL',
            'Alaska' => 'AK',
            'Arizona' => 'AZ',
            'Arkansas' => 'AR',
            'California' => 'CA',
            'Colorado' => 'CO',
            'Connecticut' => 'CT',
            'District of Columbia' => 'DC',
            'Delaware' => 'DE',
            'Florida' => 'FL',
            'Georgia' => 'GA',
            'Hawaii' => 'HI',
            'Idaho' => 'ID',
            'Illinois' => 'IL',
            'Indiana' => 'IN',
            'Iowa' => 'IA',
            'Kansas' => 'KS',
            'Kentucky' => 'KY',
            'Louisiana' => 'LA',
            'Maine' => 'ME',
            'Maryland' => 'MD',
            'Massachusetts' => 'MA',
            'Michigan' => 'MI',
            'Minnesota' => 'MN',
            'Mississippi' => 'MS',
            'Missouri' => 'MO',
            'Montana' => 'MT',
            'Nebraska' => 'NE',
            'Nevada' => 'NV',
            'New Hampshire' => 'NH',
            'New Jersey' => 'NJ',
            'New Mexico' => 'NM',
            'New York' => 'NY',
            'North Carolina' => 'NC',
            'North Dakota' => 'ND',
            'Ohio' => 'OH',
            'Oklahoma' => 'OK',
            'Oregon' => 'OR',
            'Pennsylvania' => 'PA',
            'Rhode Island' => 'RI',
            'South Carolina' => 'SC',
            'South Dakota' => 'SD',
            'Tennessee' => 'TN',
            'Texas' => 'TX',
            'Utah' => 'UT',
            'Vermont' => 'VT',
            'Virginia' => 'VA',
            'Washington' => 'WA',
            'West Virginia' => 'WV',
            'Wisconsin' => 'WI',
            'Wyoming' => 'WY'
        ];

    const AMOUNT_PATTERN_REGEX = '/^[0-9]+(?:\.[0-9]{1,2})?$/';
    const MIN_AMOUNT = 0.01;
    const MAX_AMOUNT = 99999.99;
    const MAX_AMOUNT_WEEKLY = 200;

    const MIN_CAPTCHA_LENGTH = 1;
    const MAX_CAPTCHA_LENGTH = 1024;
    const CAPTCHA_REGEX = "/^[\d\w_-]+$/";

    const DAYS_WEEK = 7;
    const MONTHS_YEAR = 12;
    const WEEKS_YEAR = 52;

    const FREQ_DAILY = 0;
    const FREQ_WEEKLY = 1;
    const FREQ_MONTHLY = 2;

    const MIN_SECURITY_QUESTION_ANSWER = 3;
    const MAX_SECURITY_QUESTION_ANSWER = 128;
    const ORDER_ACTIVE = 0;
    const ORDER_PAUSED = 1;

    public function isValidEmailString($email)
    {
        return strlen($email) >= self::MIN_EMAIL_LENGTH &&
               strlen($email) <= self::MAX_EMAIL_LENGTH &&
               $this->isEmailChars($email);
    }

    public function isValidPasswordString($password)
    {
        return strlen($password) >= self::MIN_PASSWORD_LENGTH &&
               strlen($password) <= self::MAX_PASSWORD_LENGTH &&
               $this->isPasswordChars($password);
    }

    public function isValidVerifyCodeString($verifyCode)
    {
        return strlen($verifyCode) > 1 && strlen($verifyCode) < 100 &&
            strlen($verifyCode) == strspn($verifyCode, self::HEX);
    }

    public function isValidNameString($name)
    {
        return strlen($name) >= self::MIN_NAME_LENGTH &&
               strlen($name) <= self::MAX_NAME_LENGTH &&
               strlen($name) == strspn($name, self::AZ . strtoupper(self::AZ) . '-');
    }

    public function isValidBitcoinAddress($withdrawalAddress)
    {
        if (preg_match(self::BITCOIN_ADDRESS_PATTERN_REGEX, $withdrawalAddress)) {
            return true;
        } else {
            return false;
        }
    }

    public function isValidCaptchaString($captcha)
    {
        return strlen($captcha) >= self::MIN_CAPTCHA_LENGTH &&
            strlen($captcha) <= self::MAX_CAPTCHA_LENGTH &&
            preg_match(self::CAPTCHA_REGEX, $captcha);
    }

    public function isValidAddressString($address)
    {
        return strlen($address) >= self::MIN_ADDRESS_LENGTH &&
            strlen($address) <= self::MAX_ADDRESS_LENGTH;
    }

    public function isValidCityString($city)
    {
        return strlen($city) >= self::MIN_CITY_LENGTH &&
            strlen($city) <= self::MAX_CITY_LENGTH;
    }

    public function isValidStateString($state)
    {
        return in_array($state, array_values(self::STATES));
    }

    public function isValidZipString($zip)
    {
        if (preg_match(self::ZIP_PATTERN_REGEX, $zip)) {
            return true;
        } else {
            return false;
        }
    }

    public function isValidDateFormat($date)
    {
        if (empty($date)) {
            return false;
        }
        $date = explode('-', $date);
        // index 0: YY, 1: MM, 2: DD
        return checkdate($date[1], $date[2], $date[0]);
    }

    public function isValidDatePast($date)
    {
        $today = date('Y-m-d');
        return $date >= $today;
    }

    public function isValidDateFuture($date)
    {
        $today = date('Y-m-d');
        return $date > $today;
    }

    public function isValidScheduleFrequency($frequency)
    {
        return in_array($frequency, [
            self::FREQ_DAILY,
            self::FREQ_WEEKLY,
            self::FREQ_MONTHLY
        ]);
    }

    public function isValidAmount($amount)
    {
        return $amount >= self::MIN_AMOUNT && preg_match(self::AMOUNT_PATTERN_REGEX, $amount);
    }

    public function isValidMaxAmount($amount, $maxAmount)
    {
        if (empty($maxAmount)) {
            return true;
        }
        return  $maxAmount >= $amount &&
                $maxAmount <= self::MAX_AMOUNT &&
                preg_match(self::AMOUNT_PATTERN_REGEX, $maxAmount);
    }

    public function isValidAnswerString($answer)
    {
        return strlen($answer) >= self::MIN_SECURITY_QUESTION_ANSWER &&
        strlen($answer) <= self::MAX_SECURITY_QUESTION_ANSWER &&
        $this->isAnswerChars($answer);
    }

    private function isEmailChars($email)
    {
        return $email == filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    private function isPasswordChars($password)
    {
        return strlen($password) == strspn($password,
            self::AZ . strtoupper(self::AZ) . self::NUMS . self::SPECIAL);
    }

    private function isAnswerChars($answer)
    {
        return strlen($answer) == strspn($answer,
            self::AZ . strtoupper(self::AZ) . self::NUMS . self::SPACE);
    }
}
