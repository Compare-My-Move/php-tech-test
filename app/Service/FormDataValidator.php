<?php
// App/Service/FormDataValidator.php
namespace App\Service;

class FormDataValidator
{
    public static function validate($postcode, $bedrooms, $type)
    {
        // Example of form validation
        if (empty($postcode) || empty($bedrooms) || empty($type)) {
            throw new \InvalidArgumentException('All fields are required.');
        }
        // Additional validation can go here
    }
}