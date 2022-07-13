<?php
// wp-content/plugins/ct-custom-csv-report/ct-custom-csv-report.php
/**
 * Plugin Name: Foo CSV Export
 * Description: Custom CSV Report for FooEvents
 * Version: 1.0
 * Author: createIT
 */

/**
 * FooEvents - display all orders and Attendees details
 */

add_action("admin_init", "ct_display_report5");

function ct_display_report5()
{

    if (!current_user_can('administrator')) {
        return false;
    }

    if (!isset($_GET['ct_display_report5'])) {
        return false;
    }

    ct_get_events_data();
}


function ct_get_events_data($flat_data = false)
{
    $html = '';

    $html .= '
 <style> 
 table {
     caption-side: bottom;
     border-collapse: collapse;
     width: 100%;
     margin-bottom: 1rem;
     color: #212529;
     vertical-align: top;
     border-color: #dee2e6;
}
 td {
     vertical-align:top;
}
 .container {
     max-width:1280px;
     margin:0 auto;
}
 body {
     background:#ccc;
}
 .item {
     background:#fff;
     padding:20px;
     margin:20px 0;
     border-radius:20px;
}
 .button {
     padding:10px 20px;
     background:#ccc;
     border:0;
     color:#000;
     text-decoration: none;
     margin: 10px;
     display: inline-block;
     cursor:pointer;
}
 .moreText {
     display:inline-block;
     margin:15px 0;
}
 .title {
     margin:30px 0 0 0;
}
 .itemNumber {
     font-size: 18px;
     background: #000;
     color: #fff;
     padding: 7px;
     margin: 0 5px 5px 0;
     display: inline-block;
}
    </style>
    ';

    $orders_from_date = isset($_GET['orders_from']) ? $_GET['orders_from'] : '';

    $html .= '<div class="container">';

    $html .= '<div class="item">';

    $download_csv_url = '/wp-admin/?ct_custom_download_123=yes';
    if ($orders_from_date) {
        $download_csv_url = '/wp-admin/?ct_custom_download_123=yes&orders_from=' . $orders_from_date;
        $title = 'Orders from: ' . $orders_from_date;
        $download_csv_text = '→ Export orders';
        $more_text = 'To generate CSV file with orders from ' . $orders_from_date . ' click here:';
    } else {
        $title = 'Orders all-time';
        $download_csv_text = '→ Export orders';
        $more_text = 'To generate CSV file with ALL orders click here:';
    }

    $html .= '<h2>' . $title . '</h2>';

    $html .= '<p><span class="moreText">' . $more_text . '</span><br><a href="' . $download_csv_url . '" style="display:inline-block; text-decoration:none; text-transform:uppercase; font-size:18px; background:#000; color:#fff; padding:8px 16px;">' . $download_csv_text . '</a></p>';

    $html .= '<p><hr></p>';

    $html .= '<h4 class="title">Filters</h4>';
    $html .= '<form action="/wp-admin/">';
    $html .= '<label>Show orders from: <input type="date" name="orders_from" value="' . $orders_from_date . '"></label>';
    $html .= '<input type="hidden" name="ct_display_report5" value="yes" />';
    $html .= '<input type="submit" value="Go" class="button">';
    $html .= '<a href="/wp-admin/?ct_display_report5=yes" class="button">Reset filters</a>';
    $html .= '</form>';

    $html .= '</div>';

    // compose data
    $csv_data = array();
    $columnsNames = array();

    $args = array(
        'limit' => -1,
        'orderby' => 'date',
        'order' => 'DESC',
    );

    if ($orders_from_date) {
        $args['date_created'] = '>' . (strtotime($orders_from_date));
    }


    $query = new WC_Order_Query($args);
    $orders = $query->get_orders();

    $counter = 0;
    $attendeeCounter = 0;

    foreach ($orders as $order) {
        $counter++;
        $html .= '<div class="item">';
        $html .= '<table><tr>';
        $html .= '<td><span class="itemNumber">' . $counter . '.</span></td>';
        $html .= '<td>';

        $html .= '<p>';
        $order_items = $order->get_items();
        $html .= '<p><strong>Order details:</strong>' . '<br>';
        $data1 = '';

        $html .= 'Order number: <a href="' . $order->get_edit_order_url() . '" target="_blank">' . $order->get_order_number() . '</a><br>';
        $data1 .= $order->get_date_created() . '<br>';
        foreach ($order_items as $item_id => $item) {
            $product_name = $item['name'];
            $item_quantity = $order->get_item_meta($item_id, '_qty', true);

            $data1 .= $item_quantity . ' x ' . $product_name . '<br>';
        }
        $data1 .= $order->get_total() . '<br>';

        $html .= $data1;

        $html .= '</p>';

        $data2 = '';

        $html .= '<p><strong>Billing:</strong>' . '<br>';
        $data2 .= $order->get_formatted_billing_address();
        $data2 .= $order->get_billing_email() . '<br>';
        $data2 .= $order->get_billing_phone() . '<br>';

        $html .= $data2;

        $html .= '</p>';

        $html .= '</td>';
        $html .= '<td>';
        $html .= '<p><strong>Additional Fields:</strong>' . '<br>';

        $data3 = '';

        $data3 .= 'Title: ' . $order->get_meta('additional_contact_title') . '<br>';
        $data3 .= 'First Name: ' . $order->get_meta('additional_contact_first_name') . '<br>';
        $data3 .= 'Last Name: ' . $order->get_meta('additional_contact_last_name') . '<br>';
        $data3 .= 'Registration E-mail: ' . $order->get_meta('additional_contact_email') . '<br>';
        $data3 .= 'E-mail who will receive on-site alerts: ' . $order->get_meta('additional_contact_alert') . '<br>';
        $data3 .= 'Job Title: ' . $order->get_meta('additional_contact_position') . '<br>';
        $data3 .= 'Phone: ' . $order->get_meta('additional_contact_phone') . '<br>';

        $html .= $data3;

        $html .= '</p>';
        $html .= '</td>';
        $html .= '<td>';

        $details = $order->get_meta('WooCommerceEventsOrderTickets');


        $html .= '<p><strong>Attendees:</strong></p>';

        $data4 = '';


        foreach ($details as $customAttendee) {

            foreach ($customAttendee as $key1 => $ticket) {
                $attendeeCounter++;

                $data4 .= '<strong>' . get_the_title($ticket['WooCommerceEventsProductID']) . '</strong><br>';
                $data4 .= '<strong>Attendee ' . ($key1) . '</strong>' . '<br>';
                $data4 .= 'WooCommerceEventsPurchaserFirstName: ' . $ticket['WooCommerceEventsPurchaserFirstName'] . '<br>';
                $data4 .= 'WooCommerceEventsPurchaserLastName: ' . $ticket['WooCommerceEventsPurchaserLastName'] . '<br>';
                $data4 .= 'WooCommerceEventsPurchaserEmail: ' . $ticket['WoCommerceEventsPurchaserEmail'] . '<br>';

                // collect attendee data for CSV lines
                $columnsNames['WooCommerceEventsProductID'] = '---';
                $columnsNames['AttendeeCounter'] = '---';

                $csv_data[$attendeeCounter]['WooCommerceEventsProductID'] .= get_the_title($ticket['WooCommerceEventsProductID']);
                $csv_data[$attendeeCounter]['AttendeeCounter'] .= 'Attendee ' . ($key1);

                $csv_data[$attendeeCounter]['WooCommerceEventsPurchaserFirstName'] = $ticket['WooCommerceEventsPurchaserFirstName'];
                $csv_data[$attendeeCounter]['WooCommerceEventsPurchaserLastName'] = $ticket['WooCommerceEventsPurchaserLastName'];
                $csv_data[$attendeeCounter]['WooCommerceEventsPurchaserEmail'] = $ticket['WoCommerceEventsPurchaserEmail'];
                $columnsNames['WooCommerceEventsPurchaserFirstName'] = '---';
                $columnsNames['WooCommerceEventsPurchaserLastName'] = '---';
                $columnsNames['WooCommerceEventsPurchaserEmail'] = '---';

                foreach ($ticket['WooCommerceEventsCustomAttendeeFields'] as $key => $val) {
                    $data4 .= $key . ': ' . $val . '<br>';

                    $csv_data[$attendeeCounter][$key] = $val;
                    $columnsNames[$key] = '---';
                }
                $data4 .= '<br>';
            }
        }

        $html .= $data4;

        $html .= '</td>';
        $html .= '</tr></table>';
        $html .= '</div>';
    }


    $html .= '</div> <!-- // container -->';
    $html .= '<hr>';

    if ($flat_data) {
        return array($csv_data, $columnsNames);
    }

    echo $html;

    die("end");

}


/**
 * FooEvents - generate custom CSV
 */


function ct_custom_download_123()
{
    if (isset($_GET["ct_custom_download_123"])):

        if (!current_user_can('administrator')) {
            return false;
        }

        $data = ct_get_events_data(true);
        ct_generate_csv_from_array($data[0], $data[1]);
    endif;
}

add_action("init", "ct_custom_download_123");


function ct_generate_csv_from_array($data, $columns)
{

    if (current_user_can('administrator')):
        if (is_admin()):

            // add columns
            $columns_names = array_keys($columns);

            $filename_variation = 'all-';

            $orders_from_date = isset($_GET['orders_from']) ? $_GET['orders_from'] : '';
            if ($orders_from_date) {
                $filename_variation = 'from' . $orders_from_date;
            }


            foreach ($data as $key => $line) {
                // var_dump($line);
                $data[$key] = array_merge($columns, $line);
            }

            $csv_filename = "report-" . $filename_variation . time() . ".csv";

            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $csv_filename . '"');

            $fp = fopen('php://output', 'wb');
            $myCounter = 0;

            // add column names as 0 rows
            array_unshift($data, $columns_names);

            foreach ($data as $key => $line) {
                $line = json_decode(json_encode($line), true);;

                // clean up html
                $breaks = array("<br />", "<br>", "<br/>");
                $line = str_ireplace($breaks, "\r\n", $line);

                $remove = array("<strong>", "</strong>");
                $line = str_ireplace($remove, "", $line);

                fputcsv($fp, $line, ',');

                $myCounter = $myCounter + 1;
            }
            fclose($fp);
            exit;

        endif;
    endif;
}


/**
 * FooEvents - display dashboard metabox with button
 */

add_action('wp_dashboard_setup', 'my_custom_dashboard_widgets');

function my_custom_dashboard_widgets()
{
    wp_add_dashboard_widget('ct_custom_help_widget', 'Export Custom report to CSV', 'ct_custom_dashboard_help');
}

function ct_custom_dashboard_help()
{
    echo '<a href="/wp-admin/?ct_display_report5=yes" class="button button-primary">Show all orders and events</a>';

}