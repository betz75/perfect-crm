<?php

defined('BASEPATH') or exit('No direct script access allowed');

$dimensions = $pdf->getPageDimensions();

$text_left = "left";
$text_right = "right";
if (is_rtl()) {
    $text_left = "right";
    $text_right = "left";
}

$table = '<table cellpadding="0" cellspacing="0" border="0" width="100%"><tr>';
$info_left_column  = '';

$info_right_column = '<td align="'.$text_left.'" width="50%"><div style="color:#424242;">';

$info_right_column .= format_organization_info();

$info_right_column .= '</div></td>';

$info_right_column = hooks()->apply_filters('invoicepdf_organization_info', $info_right_column, $invoice);

// Add logo
$info_left_column .= '<td width="50%" align="'.$text_right.'">'.pdf_logo_url(). '</td>';
$table .= $info_right_column.$info_left_column; 
$table .= "</tr></table>";
$pdf->writeHTML($table, true, false, false, false);
$border = '<br><div style="border-top:1px solid gray; height: 0px;"></div><br>';
$pdf->writeHTML($border, true, false, false, false);




// Bill to

$invoice_info = '<table cellpadding="0" cellspacing="0" border="0" width="100%"><tr><td width="50%" align="'.$text_left.'">';
$invoice_info .= '<b>' . _l('invoice_bill_to') . ':</b>';
$invoice_info .= '<div style="color:#424242;">';
$invoice_info .= format_customer_info($invoice, 'invoice', 'billing');
$invoice_info .= '</div></td><td width="50%" align="'.$text_right.'">';
$invoice_info .= '<br />' . _l('invoice_data_date') . ' ' . _d($invoice->date) . '<br />';

$invoice_info = hooks()->apply_filters('invoice_pdf_header_after_date', $invoice_info, $invoice);

if (!empty($invoice->duedate)) {
    $invoice_info .= _l('invoice_data_duedate') . ' ' . _d($invoice->duedate) . '<br />';
    $invoice_info = hooks()->apply_filters('invoice_pdf_header_after_due_date', $invoice_info, $invoice);
}
$invoice_info .= "</td></tr></table>";
// ship to to

if ($invoice->include_shipping == 1 && $invoice->show_shipping_on_invoice == 1) {
    $invoice_info .= '<br /><b>' . _l('ship_to') . ':</b>';
    $invoice_info .= '<div style="color:#424242;">';
    $invoice_info .= format_customer_info($invoice, 'invoice', 'shipping');
    $invoice_info .= '</div>';
}




if ($invoice->project_id && get_option('show_project_on_invoice') == 1) {
    $invoice_info .= _l('project') . ': ' . get_project_name_by_id($invoice->project_id) . '<br />';
    $invoice_info = hooks()->apply_filters('invoice_pdf_header_after_project_name', $invoice_info, $invoice);
}

$invoice_info = hooks()->apply_filters('invoice_pdf_header_before_custom_fields', $invoice_info, $invoice);

foreach ($pdf_custom_fields as $field) {
    $value = get_custom_field_value($invoice->id, $field['id'], 'invoice');
    if ($value == '') {
        continue;
    }
    $invoice_info .= $field['name'] . ': ' . $value . '<br />';
}

$invoice_info      = hooks()->apply_filters('invoice_pdf_header_after_custom_fields', $invoice_info, $invoice);
$invoice_info      = hooks()->apply_filters('invoice_pdf_info', $invoice_info, $invoice);



$pdf->writeHTML($invoice_info, true, false, false, false, $text_left);
$info_invoice_number_section = '';

$info_invoice_number_section .= '<div style="text-align: center">' . _l('invoice_pdf_heading') . ' ';
$info_invoice_number_section .= '<b style="color:#4e4e4e;"># ' . $invoice_number . '</b>';

if (get_option('show_status_on_pdf_ei') == 1) {
    $info_invoice_number_section .= ' <span style="color:rgb(' . invoice_status_color_pdf($status) . ');text-transform:uppercase;">' . format_invoice_status($status, '', false) . '</span>';
}

if ($status != Invoices_model::STATUS_PAID && $status != Invoices_model::STATUS_CANCELLED && get_option('show_pay_link_to_invoice_pdf') == 1
    && found_invoice_mode($payment_modes, $invoice->id, false)) {
    $info_invoice_number_section .= ' - <a style="color:#84c529;text-decoration:none;text-transform:uppercase;" href="' . site_url('invoice/' . $invoice->id . '/' . $invoice->hash) . '"><1b>' . _l('view_invoice_pdf_link_pay') . '</1b></a>';
}
$info_invoice_number_section .= "</div>";
$pdf->writeHTML($info_invoice_number_section, true, false, false, false, $text_left);

if ($invoice->shaam_number) {
$sham = '<div style="text-align: center;">'._l("invoice_shaam_number"). " ". $invoice->shaam_number."</div>";
$pdf->writeHTML($sham, true, false, false, false, $text_left);
}
// The Table
$pdf->Ln(hooks()->apply_filters('pdf_info_and_table_separator', 6));

// The items table
$items = get_items_table_data($invoice, 'invoice', 'pdf');

$tblhtml = $items->table();

$pdf->writeHTML($tblhtml, true, false, false, false, '');

$pdf->Ln(8);
$exchange_rate = null;
$currency = $invoice->currency_name;
    if ($invoice->exchange_rate) {
        $currency = "ILS";
        $exchange_rate = $invoice->exchange_rate;

    }
$tbltotal = '';
$tbltotal .= '<table cellpadding="6" style="font-size:' . ($font_size + 4) . 'px">';
if ($exchange_rate) {
    $tbltotal .= '
<tr>
    <td width="85%"><strong>' . _l('invoice_amount_before_exchange') . '</strong></td>
    <td width="15%">' . app_format_money($invoice->total / $exchange_rate, $invoice->currency_name) . '</td>
</tr><tr>
    <td width="85%"><strong>' . _l('invoice_exchange_rate_value') . '</strong></td>
    <td width="15%">' . app_format_money( $exchange_rate, $currency) . '</td>
</tr>';

}
$tbltotal .= '
<tr>
    <td width="85%"><strong>' . _l('invoice_subtotal') . '</strong></td>
    <td width="15%">' . app_format_money($invoice->subtotal, $currency) . '</td>
</tr>';

if (is_sale_discount_applied($invoice)) {
    $tbltotal .= '
    <tr>
        <td width="85%"><strong>' . _l('invoice_discount');
    if (is_sale_discount($invoice, 'percent')) {
        $tbltotal .= ' (' . app_format_number($invoice->discount_percent, true) . '%)';
    }
    $tbltotal .= '</strong>';
    $tbltotal .= '</td>';
    $tbltotal .= '<td width="15%">-' . app_format_money($invoice->discount_total, $currency) . '</td>
    </tr>';
}

foreach ($items->taxes() as $tax) {
    $tbltotal .= '<tr>
    <td width="85%"><strong>' . $tax['taxname'] . ' (' . app_format_number($tax['taxrate']) . '%)' . '</strong></td>
    <td width="15%">' . app_format_money($tax['total_tax'], $currency) . '</td>
</tr>';
}

if ((int) $invoice->adjustment != 0) {
    $tbltotal .= '<tr>
    <td width="85%"><strong>' . _l('invoice_adjustment') . '</strong></td>
    <td width="15%">' . app_format_money($invoice->adjustment, $currency) . '</td>
</tr>';
}

$tbltotal .= '
<tr style="background-color:#f0f0f0;">
    <td width="85%"><strong>' . _l('invoice_total') . '</strong></td>
    <td width="15%">' . app_format_money($invoice->total, $currency) . '</td>
</tr>';

if (count($invoice->payments) > 0 && get_option('show_total_paid_on_invoice') == 1) {
    $tbltotal .= '
    <tr>
        <td width="85%"><strong>' . _l('invoice_total_paid') . '</strong></td>
        <td width="15%">-' . app_format_money(sum_from_table(db_prefix() . 'invoicepaymentrecords', [
        'field' => 'amount',
        'where' => [
            'invoiceid' => $invoice->id,
        ],
    ]), $currency) . '</td>
    </tr>';
}

if (get_option('show_credits_applied_on_invoice') == 1 && $credits_applied = total_credits_applied_to_invoice($invoice->id)) {
    $tbltotal .= '
    <tr>
        <td width="85%"><strong>' . _l('applied_credits') . '</strong></td>
        <td width="15%">-' . app_format_money($credits_applied, $currency) . '</td>
    </tr>';
}

if (get_option('show_amount_due_on_invoice') == 1 && $invoice->status != Invoices_model::STATUS_CANCELLED) {
    $tbltotal .= '<tr style="background-color:#f0f0f0;">
       <td width="85%"><strong>' . _l('invoice_amount_due') . '</strong></td>
       <td width="15%">' . app_format_money($invoice->total_left_to_pay, $currency) . '</td>
   </tr>';
}

$tbltotal .= '</table>';
$pdf->writeHTML($tbltotal, true, false, false, false, '');

if (get_option('total_to_words_enabled') == 1) {
    // Set the font bold
    $pdf->SetFont($font_name, 'B', $font_size);
    $pdf->writeHTMLCell('', '', '', '', _l('num_word') . ': ' . $CI->numberword->convert($invoice->total, $invoice->currency_name), 0, 1, false, true, 'C', true);
    // Set the font again to normal like the rest of the pdf
    $pdf->SetFont($font_name, '', $font_size);
    $pdf->Ln(4);
}

if (count($invoice->payments) > 0 && get_option('show_transactions_on_invoice_pdf') == 1) {
    $pdf->Ln(4);
    $border = 'border-bottom-color:#000000;border-bottom-width:1px;border-bottom-style:solid; 1px solid black;';
    $pdf->SetFont($font_name, 'B', $font_size);
    $pdf->Cell(0, 0, _l('invoice_received_payments') . ':', 0, 1, 'L', 0, '', 0);
    $pdf->SetFont($font_name, '', $font_size);
    $pdf->Ln(4);
    $tblhtml = '<table width="100%" bgcolor="#fff" cellspacing="0" cellpadding="5" border="0">
        <tr height="20"  style="color:#000;border:1px solid #000;">
        <th width="25%;" style="' . $border . '">' . _l('invoice_payments_table_number_heading') . '</th>
        <th width="25%;" style="' . $border . '">' . _l('invoice_payments_table_mode_heading') . '</th>
        <th width="25%;" style="' . $border . '">' . _l('invoice_payments_table_date_heading') . '</th>
        <th width="25%;" style="' . $border . '">' . _l('invoice_payments_table_amount_heading') . '</th>
    </tr>';
    $tblhtml .= '<tbody>';
    foreach ($invoice->payments as $payment) {
        $payment_name = $payment['name'];
        if (!empty($payment['paymentmethod'])) {
            $payment_name .= ' - ' . $payment['paymentmethod'];
        }
        $tblhtml .= '
            <tr>
            <td>' . $payment['paymentid'] . '</td>
            <td>' . $payment_name . '</td>
            <td>' . _d($payment['date']) . '</td>
            <td>' . app_format_money($payment['amount'], $invoice->currency_name) . '</td>
            </tr>
        ';
    }
    $tblhtml .= '</tbody>';
    $tblhtml .= '</table>';
    $pdf->writeHTML($tblhtml, true, false, false, false, '');
}

if (found_invoice_mode($payment_modes, $invoice->id, true, true)) {
    $pdf->Ln(4);
    $pdf->SetFont($font_name, 'B', $font_size);
    $pdf->Cell(0, 0, _l('invoice_html_offline_payment') . ':', 0, 1, 'L', 0, '', 0);
    $pdf->SetFont($font_name, '', $font_size);

    foreach ($payment_modes as $mode) {
        if (is_numeric($mode['id'])) {
            if (!is_payment_mode_allowed_for_invoice($mode['id'], $invoice->id)) {
                continue;
            }
        }
        if (isset($mode['show_on_pdf']) && $mode['show_on_pdf'] == 1) {
            $pdf->Ln(1);
            $pdf->Cell(0, 0, $mode['name'], 0, 1, 'L', 0, '', 0);
            $pdf->Ln(2);
            $pdf->writeHTMLCell('', '', '', '', $mode['description'], 0, 1, false, true, 'L', true);
        }
    }
}
if ($invoice->sale_agent && get_option('show_sale_agent_on_invoices') == 1) {
    $sale_agent = _l('sale_agent_string') . ': ' . get_staff_full_name($invoice->sale_agent) . '<br />';
    $pdf->writeHTML($sale_agent, true, false, false, false, $text_left);
}
if (!empty($invoice->clientnote)) {
    $title = _l('invoice_note');
    $content = $invoice->clientnote;
    $title = "<br><br><br><strong>$title</strong><br>";
    $pdf->writeHTML($title, true, false, false, false, $text_left);
    $pdf->writeHTML($content, true, false, false, false, $text_left);

}

if (!empty($invoice->terms)) {
    $title = _l('terms_and_conditions');
    $content = $invoice->terms;
    $title = "<br><br><br><strong>$title:</strong><br>";
    $pdf->writeHTML($title, true, false, false, false, $text_left);
    $pdf->writeHTML($content, true, false, false, false, $text_left);
}