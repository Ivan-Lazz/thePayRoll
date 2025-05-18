<?php
require_once __DIR__ . '/../lib/fpdf/fpdf.php';

/**
 * PDFGenerator - Service for generating payslip PDFs
 */
class PDFGenerator {
    private $basePath;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->basePath = PDF_PATH;
    }
    
    /**
     * Generate Agent payslip (Variation 1)
     *
     * @param array $data Payslip data
     * @return array Path information
     */
    public function generateAgentPayslip($data) {
        // Ensure agent directory exists
        if (!file_exists(PDF_AGENT_PATH)) {
            mkdir(PDF_AGENT_PATH, 0755, true);
        }
        
        // Generate filename
        $filename = 'agent_' . $data['payslip_no'] . '_' . date('Ymd_His') . '.pdf';
        $fullPath = PDF_AGENT_PATH . '/' . $filename;
        $relativePath = '/pdfs/agent/' . $filename;
        
        // Create PDF
        $pdf = new FPDF();
        $pdf->AddPage();
        
        // Set document information
        $pdf->SetTitle('Agent Payslip - ' . $data['payslip_no']);
        $pdf->SetAuthor('Your Company');
        $pdf->SetCreator('Pay Slip Generator');
        
        // Add company logo if exists
        if (file_exists(COMPANY_LOGO)) {
            $pdf->Image(COMPANY_LOGO, 10, 10, 30);
        }
        
        // Add company name
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(0, 10, COMPANY_NAME, 0, 1, 'R');
        
        $pdf->Ln(10);
        
        // Add payslip header
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(0, 10, 'AGENT PAYSLIP', 0, 1, 'C');
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(0, 5, 'Payslip No: ' . $data['payslip_no'], 0, 1, 'C');
        
        $pdf->Ln(10);
        
        // Employee information
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 10, 'Employee Information', 0, 1);
        
        $pdf->SetFont('Arial', '', 10);
        
        // Agent name
        $pdf->Cell(50, 8, 'Agent Name:', 0);
        $pdf->Cell(0, 8, $data['employee_name'], 0, 1);
        
        // Employee ID
        $pdf->Cell(50, 8, 'Employee ID:', 0);
        $pdf->Cell(0, 8, $data['employee_id'], 0, 1);
        
        // Payment date
        $pdf->Cell(50, 8, 'Payment Date:', 0);
        $pdf->Cell(0, 8, date('F d, Y', strtotime($data['payment_date'])), 0, 1);
        
        $pdf->Ln(5);
        
        // Add payment details
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 10, 'Payment Information', 0, 1);
        
        // Table header
        $pdf->SetFillColor(240, 240, 240);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(100, 8, 'Description', 1, 0, 'L', true);
        $pdf->Cell(60, 8, 'Amount', 1, 1, 'R', true);
        
        // Table content
        $pdf->SetFont('Arial', '', 10);
        
        // Salary
        $pdf->Cell(100, 8, 'Salary', 1, 0, 'L');
        $pdf->Cell(60, 8, number_format($data['salary'], 2), 1, 1, 'R');
        
        // Bonus
        $pdf->Cell(100, 8, 'Bonus', 1, 0, 'L');
        $pdf->Cell(60, 8, number_format($data['bonus'], 2), 1, 1, 'R');
        
        // Total
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(100, 8, 'Total', 1, 0, 'L', true);
        $pdf->Cell(60, 8, number_format($data['total_salary'], 2), 1, 1, 'R', true);
        
        $pdf->Ln(10);
        
        // Footer
        $pdf->SetFont('Arial', 'I', 8);
        $pdf->Cell(0, 10, 'This is a system-generated document. No signature required.', 0, 1, 'C');
        
        // Output PDF
        $pdf->Output('F', $fullPath);
        
        return [
            'filename' => $filename,
            'path' => $relativePath,
            'full_path' => $fullPath
        ];
    }
    
    /**
     * Generate Admin payslip (Variation 2)
     *
     * @param array $data Payslip data
     * @return array Path information
     */
    public function generateAdminPayslip($data) {
        // Ensure admin directory exists
        if (!file_exists(PDF_ADMIN_PATH)) {
            mkdir(PDF_ADMIN_PATH, 0755, true);
        }
        
        // Generate filename
        $filename = 'admin_' . $data['payslip_no'] . '_' . date('Ymd_His') . '.pdf';
        $fullPath = PDF_ADMIN_PATH . '/' . $filename;
        $relativePath = '/pdfs/admin/' . $filename;
        
        // Create PDF
        $pdf = new FPDF();
        $pdf->AddPage();
        
        // Set document information
        $pdf->SetTitle('Admin Payslip - ' . $data['payslip_no']);
        $pdf->SetAuthor('Your Company');
        $pdf->SetCreator('Pay Slip Generator');
        
        // Add company logo if exists
        if (file_exists(COMPANY_LOGO)) {
            $pdf->Image(COMPANY_LOGO, 10, 10, 30);
        }
        
        // Add company name
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(0, 10, COMPANY_NAME, 0, 1, 'R');
        
        $pdf->Ln(10);
        
        // Add payslip header
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(0, 10, 'ADMIN PAYSLIP', 0, 1, 'C');
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(0, 5, 'Payslip No: ' . $data['payslip_no'], 0, 1, 'C');
        
        $pdf->Ln(10);
        
        // Employee information
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 10, 'Employee Information', 0, 1);
        
        $pdf->SetFont('Arial', '', 10);
        
        // Agent name
        $pdf->Cell(50, 8, 'Agent Name:', 0);
        $pdf->Cell(0, 8, $data['employee_name'], 0, 1);
        
        // Employee ID
        $pdf->Cell(50, 8, 'Employee ID:', 0);
        $pdf->Cell(0, 8, $data['employee_id'], 0, 1);
        
        $pdf->Ln(5);
        
        // Banking details
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 10, 'Banking Information', 0, 1);
        
        $pdf->SetFont('Arial', '', 10);
        
        // Bank Name
        $pdf->Cell(50, 8, 'Bank Name:', 0);
        $pdf->Cell(0, 8, $data['bank_details']['preferred_bank'], 0, 1);
        
        // Account Number
        $pdf->Cell(50, 8, 'Account Number:', 0);
        $pdf->Cell(0, 8, $data['bank_details']['bank_account_number'], 0, 1);
        
        // Account Name
        $pdf->Cell(50, 8, 'Account Name:', 0);
        $pdf->Cell(0, 8, $data['bank_details']['bank_account_name'], 0, 1);
        
        $pdf->Ln(5);
        
        // Payment details
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 10, 'Payment Information', 0, 1);
        
        $pdf->SetFont('Arial', '', 10);
        
        // Person In Charge
        $pdf->Cell(50, 8, 'Person In Charge:', 0);
        $pdf->Cell(0, 8, $data['person_in_charge'], 0, 1);
        
        // Payment Date
        $pdf->Cell(50, 8, 'Payment Date:', 0);
        $pdf->Cell(0, 8, date('F d, Y', strtotime($data['payment_date'])), 0, 1);
        
        // Table header
        $pdf->SetFillColor(240, 240, 240);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(100, 8, 'Description', 1, 0, 'L', true);
        $pdf->Cell(60, 8, 'Amount', 1, 1, 'R', true);
        
        // Table content
        $pdf->SetFont('Arial', '', 10);
        
        // Salary
        $pdf->Cell(100, 8, 'Salary', 1, 0, 'L');
        $pdf->Cell(60, 8, number_format($data['salary'], 2), 1, 1, 'R');
        
        // Bonus
        $pdf->Cell(100, 8, 'Bonus', 1, 0, 'L');
        $pdf->Cell(60, 8, number_format($data['bonus'], 2), 1, 1, 'R');
        
        // Total
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(100, 8, 'Total', 1, 0, 'L', true);
        $pdf->Cell(60, 8, number_format($data['total_salary'], 2), 1, 1, 'R', true);
        
        $pdf->Ln(5);
        
        // Payment Status
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(50, 8, 'Payment Status:', 0);
        
        // Set color based on status
        if ($data['payment_status'] === 'Paid') {
            $pdf->SetTextColor(0, 128, 0); // Green
        } else if ($data['payment_status'] === 'Pending') {
            $pdf->SetTextColor(255, 128, 0); // Orange
        } else {
            $pdf->SetTextColor(255, 0, 0); // Red
        }
        
        $pdf->Cell(0, 8, $data['payment_status'], 0, 1);
        
        // Reset text color
        $pdf->SetTextColor(0);
        
        $pdf->Ln(10);
        
        // Signature line
        $pdf->Cell(0, 8, 'Authorized by: _________________________', 0, 1);
        $pdf->Cell(0, 8, 'Date: _________________________', 0, 1);
        
        $pdf->Ln(5);
        
        // Footer
        $pdf->SetFont('Arial', 'I', 8);
        $pdf->Cell(0, 10, 'This is a system-generated document.', 0, 1, 'C');
        
        // Output PDF
        $pdf->Output('F', $fullPath);
        
        return [
            'filename' => $filename,
            'path' => $relativePath,
            'full_path' => $fullPath
        ];
    }
}