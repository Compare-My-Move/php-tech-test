<?php

namespace App\Controller;

use App\Logger\LoggerFactory;
use App\Service\CompanyMatcher;
use App\Service\FormDataValidator;

class FormController extends Controller
{
    public function index()
    {
        $this->render('form.twig');
    }

    public function submit()
    {
        try {
            $logger = LoggerFactory::create();
            $matcher = new CompanyMatcher($this->db());

            $postcode = $_POST['postcode'] ?? '';
            $bedrooms = $_POST['bedrooms'] ?? '';
            $type     = $_POST['type'] ?? '';
            $postcode = htmlspecialchars(trim($postcode), ENT_QUOTES, 'UTF-8');
            $bedrooms = filter_var($bedrooms, FILTER_VALIDATE_INT);
            $type = htmlspecialchars(trim($type), ENT_QUOTES, 'UTF-8');
            $logger->info('Form submitted', [
                'postcode' => $postcode,
                'bedrooms' => $bedrooms,
                'type' => $type
            ]);
            // Validate inputs
            FormDataValidator::validate($postcode, $bedrooms, $type);

            $matchedCompanies = $matcher->match($postcode, $bedrooms, $type);

            // Log if any matched company has no credits
            foreach ($matchedCompanies as $company) {
                if ($company['credits'] <= 0) {
                    $logger->warning("Company '{$company['name']}' has run out of credits.");
                }
            }

            $this->render('results.twig', [
                'companies' => $matchedCompanies,
            ]);
        } catch (\Exception $e) {
            $logger->error('Error processing form submission: ' . $e->getMessage());
            $this->render('error.twig', ['message' => 'An error occurred while processing your request.']);
        }
    }
}
