<?php

defined('BASEPATH') or exit('No direct script access allowed');

$dimensions = $pdf->getPageDimensions();

$text_left = "left";
$text_right = "right";
if (is_rtl()) {
    $text_left = "right";
    $text_right = "left";
}

$info_left_column  = '';


$info_left_column .= pdf_logo_url();


$dates = _l('estimate_data_date') . ': ' . _d($estimate->date) . '<br />';

if (!empty($estimate->expirydate)) {
    $dates .= _l('estimate_data_expiry_date') . ': ' . _d($estimate->expirydate) . '<br />';
}


// Add logo
// Write top left logo and right column info/text



$organization_info = '<div style="color:#424242;">';
$organization_info .= format_organization_info();
$organization_info .= '</div>';
// Estimate to
$estimate_info = '<div style="text-align:right"><b>' . _l('estimate_to') . '</b>';
$estimate_info .= '<div style="color:#424242;">';
$estimate_info .= format_customer_info($estimate, 'estimate', 'billing');
$estimate_info .= '</div></div>';

$ship_to_info = '';

// ship to to
if (FALSE && $estimate->include_shipping == 1 && $estimate->show_shipping_on_estimate == 1) {
    $ship_to_info .= '<br /><b>' . _l('ship_to') . '</b>';

    $ship_to_info .= '<div style="color:#424242;">';
    $ship_to_info .= format_customer_info($estimate, 'estimate', 'shipping');
    $ship_to_info .= '</div><br><br>';
}



if (!empty($estimate->reference_no)) {
    $estimate_info .= _l('reference_no') . ': ' . $estimate->reference_no . '<br />';
}



if ($estimate->project_id && get_option('show_project_on_estimate') == 1) {
    $estimate_info .= _l('project') . ': ' . get_project_name_by_id($estimate->project_id) . '<br />';
}

foreach ($pdf_custom_fields as $field) {
    $value = get_custom_field_value($estimate->id, $field['id'], 'estimate');
    if ($value == '') {
        continue;
    }
    $estimate_info .= $field['name'] . ': ' . $value . '<br />';
}

$left_info  = $swap == '1' ? $estimate_info : $organization_info;
$right_info = $swap == '1' ? $organization_info : $estimate_info;

//pdf_multi_row($left_info, $right_info, $pdf, ($dimensions['wk'] / 2) - $dimensions['lm']);

// The Table
$markup = '<table><tr><td align="'.$text_left.'">';
$markup .= $organization_info;
$markup .= '</td><td align="'.$text_right.'">';
$markup .= $info_left_column;
$markup .= '</td></tr></table>';
$markup .= '<br><div style="border-top: 1px solid gray; height:0;"></div><br>';
$markup .= '<table><tr><td align="'.$text_left.'">'.$estimate_info."<br>". $ship_to_info;
$markup .= '</td><td align="'.$text_right.'">';
$markup .= $dates;
$markup .= '</td></tr></table>';

$pdf->writeHTML($markup, true, false, true, false, '');
$estimate_number_section = '';

$estimate_number_section .= '<div style="text-align: center">' . _l('estimate_pdf_heading') . '';
$estimate_number_section .= '<b style="color:#4e4e4e;"># ' . $estimate_number . '</b>';

if (get_option('show_status_on_pdf_ei') == 1) {
    $estimate_number_section .= '<span style="color:rgb(' . estimate_status_color_pdf($status) . ');text-transform:uppercase;">' . format_estimate_status($status, '', false) . '</span>';
}
$estimate_number_section .= "</div><br>";

$pdf->writeHTML($estimate_number_section, true, false, true, false, '');

// The items table
$items = get_items_table_data($estimate, 'estimate', 'pdf');

$tblhtml = $items->table();

$pdf->writeHTML($tblhtml, true, false, false, false, '');
$agent = '';
if ($estimate->sale_agent && get_option('show_sale_agent_on_estimates') == 1) {
    $agent .= _l('sale_agent_string') . ': ' . get_staff_full_name($estimate->sale_agent) . '<br />';
    $pdf->writeHTML($agent, true, false, false, false, '');
}
$exchange_rate = null;
$currency = $estimate->currency_name;
if ($estimate->exchange_rate) {
    $currency = "ILS";
    $exchange_rate = $estimate->exchange_rate;
}
$pdf->Ln(8);
$tbltotal = '';
$tbltotal .= '<table cellpadding="6" style="font-size:' . ($font_size + 4) . 'px">';
if ($exchange_rate) {
$tbltotal .= '
    <tr>
        <td align="right" width="85%"><strong>' . _l('estimate_subtotal') . '</strong></td>
        <td align="right" width="15%">' . app_format_money($estimate->total / $exchange_rate, $estimate->currency) . '</td>
    </tr>';
    $tbltotal .= '
    <tr>
        <td align="right" width="85%"><strong>' . _l('estimate_subtotal') . '</strong></td>
        <td align="right" width="15%">' . app_format_money($exchange_rate, $currency) . '</td>
    </tr>';
}
$tbltotal .= '
<tr>
    <td align="right" width="85%"><strong>' . _l('estimate_subtotal') . '</strong></td>
    <td align="right" width="15%">' . app_format_money($estimate->subtotal, $currency) . '</td>
</tr>';

if (is_sale_discount_applied($estimate)) {
    $tbltotal .= '
    <tr>
        <td align="right" width="85%"><strong>' . _l('estimate_discount');
    if (is_sale_discount($estimate, 'percent')) {
        $tbltotal .= ' (' . app_format_number($estimate->discount_percent, true) . '%)';
    }
    $tbltotal .= '</strong>';
    $tbltotal .= '</td>';
    $tbltotal .= '<td align="right" width="15%">-' . app_format_money($estimate->discount_total, $currency) . '</td>
    </tr>';
}

foreach ($items->taxes() as $tax) {
    $tbltotal .= '<tr>
    <td align="right" width="85%"><strong>' . $tax['taxname'] . ' (' . app_format_number($tax['taxrate']) . '%)' . '</strong></td>
    <td align="right" width="15%">' . app_format_money($tax['total_tax'] * $exchange_rate, $currency) . '</td>
</tr>';
}

if ((int)$estimate->adjustment != 0) {
    $tbltotal .= '<tr>
    <td align="right" width="85%"><strong>' . _l('estimate_adjustment') . '</strong></td>
    <td align="right" width="15%">' . app_format_money($estimate->adjustment, $currency) . '</td>
</tr>';
}

$tbltotal .= '
<tr style="background-color:#f0f0f0;">
    <td align="right" width="85%"><strong>' . _l('estimate_total') . '</strong></td>
    <td align="right" width="15%">' . app_format_money($estimate->total, $currency) . '</td>
</tr>';

$tbltotal .= '</table>';

$pdf->writeHTML($tbltotal, true, false, false, false, '');

if (get_option('total_to_words_enabled') == 1) {
    // Set the font bold
    $pdf->SetFont($font_name, 'B', $font_size);
    $pdf->writeHTMLCell('', '', '', '', _l('num_word') . ': ' . $CI->numberword->convert($estimate->total, $currency), 0, 1, false, true, 'C', true);
    // Set the font again to normal like the rest of the pdf
    $pdf->SetFont($font_name, '', $font_size);
    $pdf->Ln(4);
}

if (!empty($estimate->clientnote)) {
    $markup = '';
    $markup = '<div style="text-align:right"><b>';
    $markup .= _l('estimate_note');
    $markup .= "</b><br>";
    $markup .= $estimate->clientnote;

    $pdf->writeHTML($markup, true, false, true, false, '');

}

if (!empty($estimate->terms)) {
    $markup = '';
    $markup = '<br><div style="text-align:right"><b>';
    $markup .= _l('terms_and_conditions');
    $markup .= "</b><br>";
    $markup .= $estimate->terms;
    $pdf->writeHTML($markup, true, false, true, false, '');
}
