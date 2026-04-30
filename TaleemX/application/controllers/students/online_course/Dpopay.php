<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Dpopay extends Student_Controller
{
    public $pay_method = "";
    public $setting    = "";
    public $currency_name = "";

    public function __construct()
    {
        parent::__construct();
        $this->load->model(array(
            'course_model',
            'coursesection_model',
            'courselesson_model',
            'studentcourse_model',
            'coursequiz_model',
            'course_payment_model',
            'courseofflinepayment_model',
            'coursereport_model',
            'currency_model'
        ));

        $this->pay_method    = $this->paymentsetting_model->getActiveMethod();
        $this->setting       = $this->setting_model->get();
        $this->load->library('course_mail_sms');
        $student_currency_id = $this->session->userdata('student')['currency'] ?? null;
        if (!empty($student_currency_id)) {
            $currency_row = $this->currency_model->get($student_currency_id);
            $this->currency_name = !empty($currency_row) ? $currency_row->short_name : 'USD';
        } else {
            $this->currency_name = 'USD';
        }
    }

    /**
     * Show payment details + card form for single course purchase
     */
    public function index()
    {
        $data               = array();
        $data['params']     = $this->session->userdata('course_amount');
        $data['setting']    = $this->setting;
        $data['error']      = array();
        $this->load->view('user/studentcourse/online_course/dpopay/index', $data);
    }

    /**
     * Handle card submission and charge via DPO for single course
     */
    public function pay()
    {
        $this->form_validation->set_rules('creditcardnumber', 'Credit Card Number', 'trim|required|xss_clean');
        $this->form_validation->set_rules('creditcardexpiry', 'Credit Card Expiry', 'trim|required|xss_clean');
        $this->form_validation->set_rules('creditcardcvv', 'Credit Card CVV', 'trim|required|xss_clean');
        $this->form_validation->set_rules('cardholdername', 'Card Holder Name', 'trim|required|xss_clean');

        $params            = $this->session->userdata('course_amount');
        $data['params']    = $params;
        $data['setting']   = $this->setting;
        $data['error']     = array();

        if ($this->form_validation->run() == false) {
            $this->load->view('user/studentcourse/online_course/dpopay/index', $data);
            return;
        }

        if (empty($params) || !isset($params['total_amount'])) {
            $data['error'] = array('ResultExplanation' => 'Invalid or expired payment session. Please try again.');
            $this->load->view('user/studentcourse/online_course/dpopay/index', $data);
            return;
        }

        $CompanyToken    = $this->pay_method->api_secret_key;
        $PaymentCurrency = isset($params['currency_name']) && !empty($params['currency_name']) ? $params['currency_name'] : $this->currency_name;
        $total_amount    = number_format((float) $params['total_amount'], 2, '.', '');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://secure.3gdirectpay.com/API/v6/');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);

        $redirect_url = base_url('students/online_course/course_payment/paymentsuccess');
        $back_url     = base_url('students/online_course/course_payment/paymentfailed');

        $xml_body = "<?xml version='1.0' encoding='utf-8'?>"
            . "<API3G>"
            . "<CompanyToken>" . $CompanyToken . "</CompanyToken>"
            . "<Request>createToken</Request>"
            . "<Transaction>"
            . "<PaymentAmount>" . $total_amount . "</PaymentAmount>"
            . "<PaymentCurrency>" . $PaymentCurrency . "</PaymentCurrency>"
            . "<CompanyRef>online_course</CompanyRef>"
            . "<RedirectURL>" . $redirect_url . "</RedirectURL>"
            . "<BackURL>" . $back_url . "</BackURL>"
            . "<CompanyRefUnique>0</CompanyRefUnique>"
            . "<PTL>5</PTL>"
            . "</Transaction>"
            . "<Services>"
            . "<Service>"
            . "<ServiceType>86280</ServiceType>"
            . "<ServiceDescription>Online Course</ServiceDescription>"
            . "<ServiceDate>" . date('Y-m-d') . "</ServiceDate>"
            . "</Service>"
            . "</Services>"
            . "</API3G>";

        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml_body);
        $headers = array();
        $headers[] = 'Content-Type: application/xml';
        $headers[] = 'Accept: application/xml';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            $data['error'] = array('ResultExplanation' => curl_error($ch));
            curl_close($ch);
            $this->load->view('user/studentcourse/online_course/dpopay/index', $data);
            return;
        }

        $xml = @simplexml_load_string($result);
        if ($xml === false) {
            $data['error'] = array(
                'ResultExplanation' => 'Invalid gateway response',
                'message'           => substr($result, 0, 500),
            );
            curl_close($ch);
            $this->load->view('user/studentcourse/online_course/dpopay/index', $data);
            return;
        }

        $array = json_decode(json_encode($xml), true);
        curl_close($ch);

        if (isset($array['Result']) && $array['Result'] == '000') {
            $TransToken = $array['TransToken'];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://secure.3gdirectpay.com/API/v6/');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);

            $xml_charge = '<?xml version="1.0" encoding="utf-8"?>'
                . '<API3G>'
                . '<CompanyToken>' . $CompanyToken . '</CompanyToken>'
                . '<Request>chargeTokenCreditCard</Request>'
                . '<TransactionToken>' . $TransToken . '</TransactionToken>'
                . '<CreditCardNumber>' . $this->input->post('creditcardnumber') . '</CreditCardNumber>'
                . '<CreditCardExpiry>' . $this->input->post('creditcardexpiry') . '</CreditCardExpiry>'
                . '<CreditCardCVV>' . $this->input->post('creditcardcvv') . '</CreditCardCVV>'
                . '<CardHolderName>' . $this->input->post('cardholdername') . '</CardHolderName>'
                . '<ChargeType></ChargeType>'
                . '</API3G>';

            curl_setopt($ch, CURLOPT_POSTFIELDS, $xml_charge);
            $headers = array();
            $headers[] = 'Content-Type: application/xml';
            $headers[] = 'Accept: application/xml';
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            $result1 = curl_exec($ch);
            if (curl_errno($ch)) {
                $data['error'] = array('ResultExplanation' => curl_error($ch));
                curl_close($ch);
                $this->load->view('user/studentcourse/online_course/dpopay/index', $data);
                return;
            }

            $xml2 = @simplexml_load_string($result1);
            if ($xml2 === false) {
                $data['error'] = array(
                    'ResultExplanation' => 'Invalid gateway response',
                    'message'           => substr($result1, 0, 500),
                );
                curl_close($ch);
                $this->load->view('user/studentcourse/online_course/dpopay/index', $data);
                return;
            }

            $json = json_decode(json_encode($xml2), true);
            curl_close($ch);

            if (isset($json['ResultExplanation']) && $json['ResultExplanation'] == 'Transaction Charged') {
                $this->success($array['TransRef']);
                return;
            } else {
                $data['error'] = $json;
                $this->load->view('user/studentcourse/online_course/dpopay/index', $data);
                return;
            }
        } else {
            $data['error'] = $array;
            $this->load->view('user/studentcourse/online_course/dpopay/index', $data);
            return;
        }
    }

    /**
     * Persist successful course payment and send notifications
     *
     * @param string $TransRef
     */
    private function success($TransRef)
    {
        $params = $this->session->userdata('course_amount');
        if (empty($params)) {
            redirect(base_url('students/online_course/course_payment/paymentfailed'));
            return;
        }

        $payment_data = array(
            'date'                     => date('Y-m-d'),
            'student_id'               => $params['student_id'],
            'guest_id'                 => $params['guest_id'],
            'online_courses_id'        => $params['courseid'],
            'course_name'              => $params['course_name'],
            'actual_price'             => $params['actual_amount'],
            'paid_amount'              => $params['total_amount'],
            'payment_type'             => 'Online',
            'transaction_id'           => $TransRef,
            'note'                     => "Online course fees deposit through DPO Txn ID: " . $TransRef,
            'payment_mode'             => 'DPO',
            'processing_charge_type'   => isset($params['processing_charge_type']) ? $params['processing_charge_type'] : null,
            'processing_charge_amount' => isset($params['gateway_processing_charge']) ? $params['gateway_processing_charge'] : 0,
        );

        $this->course_payment_model->add($payment_data);

        if (!empty($params['courseid'])) {
            $sender_details = array(
                'email'            => $params['email'],
                'courseid'         => $params['courseid'],
                'class'            => $params['class'],
                'class_section_id' => $params['class_sections'],
                'section'          => $params['section'],
                'title'            => $params['course_name'],
                'price'            => $params['total_amount'],
                'discount'         => $params['discount'],
                'assign_teacher'   => $params['staff'],
                'purchase_date'    => $this->customlib->dateformat(date('Y-m-d')),
            );

            if (!empty($params['student_id'])) {
                $this->course_mail_sms->purchasemail('online_course_purchase', $sender_details);
            } elseif (!empty($params['guest_id'])) {
                $this->course_mail_sms->purchasemail('online_course_purchase_for_guest_user', $sender_details);
            }
        }

        redirect(base_url("students/online_course/course_payment/paymentsuccess"));
    }

    /**
     * Guest checkout summary (cart) + card form
     */
    public function guest()
    {
        $data               = array();
        $data['params']     = $this->session->userdata('cart_data');
        $data['setting']    = $this->setting;
        $data['error']      = array();
        $this->load->view('user/studentcourse/online_course/dpopay/guest_course/index', $data);
    }

    /**
     * Guest cart payment via DPO
     */
    public function guestpay()
    {
        $this->form_validation->set_rules('creditcardnumber', 'Credit Card Number', 'trim|required|xss_clean');
        $this->form_validation->set_rules('creditcardexpiry', 'Credit Card Expiry', 'trim|required|xss_clean');
        $this->form_validation->set_rules('creditcardcvv', 'Credit Card CVV', 'trim|required|xss_clean');
        $this->form_validation->set_rules('cardholdername', 'Card Holder Name', 'trim|required|xss_clean');

        $cart_data        = $this->session->userdata('cart_data');
        $data['params']   = $cart_data;
        $data['setting']  = $this->setting;
        $data['error']    = array();

        if ($this->form_validation->run() == false) {
            $this->load->view('user/studentcourse/online_course/dpopay/guest_course/index', $data);
            return;
        }

        if (empty($cart_data)) {
            $data['error'] = array('ResultExplanation' => 'Invalid or expired cart session. Please try again.');
            $this->load->view('user/studentcourse/online_course/dpopay/guest_course/index', $data);
            return;
        }

        $total_cart_amount = (float) $this->input->post('total_cart_amount');
        $total_amount      = number_format($total_cart_amount, 2, '.', '');

        $CompanyToken    = $this->pay_method->api_secret_key;
        $PaymentCurrency = $this->currency_name;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://secure.3gdirectpay.com/API/v6/');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);

        $redirect_url = base_url('students/online_course/course_payment/paymentsuccess');
        $back_url     = base_url('students/online_course/course_payment/paymentfailed');

        $xml_body = "<?xml version='1.0' encoding='utf-8'?>"
            . "<API3G>"
            . "<CompanyToken>" . $CompanyToken . "</CompanyToken>"
            . "<Request>createToken</Request>"
            . "<Transaction>"
            . "<PaymentAmount>" . $total_amount . "</PaymentAmount>"
            . "<PaymentCurrency>" . $PaymentCurrency . "</PaymentCurrency>"
            . "<CompanyRef>online_course_guest</CompanyRef>"
            . "<RedirectURL>" . $redirect_url . "</RedirectURL>"
            . "<BackURL>" . $back_url . "</BackURL>"
            . "<CompanyRefUnique>0</CompanyRefUnique>"
            . "<PTL>5</PTL>"
            . "</Transaction>"
            . "<Services>"
            . "<Service>"
            . "<ServiceType>86280</ServiceType>"
            . "<ServiceDescription>Online Course Guest Purchase</ServiceDescription>"
            . "<ServiceDate>" . date('Y-m-d') . "</ServiceDate>"
            . "</Service>"
            . "</Services>"
            . "</API3G>";

        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml_body);
        $headers = array();
        $headers[] = 'Content-Type: application/xml';
        $headers[] = 'Accept: application/xml';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            $data['error'] = array('ResultExplanation' => curl_error($ch));
            curl_close($ch);
            $this->load->view('user/studentcourse/online_course/dpopay/guest_course/index', $data);
            return;
        }

        $xml = @simplexml_load_string($result);
        if ($xml === false) {
            $data['error'] = array(
                'ResultExplanation' => 'Invalid gateway response',
                'message'           => substr($result, 0, 500),
            );
            curl_close($ch);
            $this->load->view('user/studentcourse/online_course/dpopay/guest_course/index', $data);
            return;
        }

        $array = json_decode(json_encode($xml), true);
        curl_close($ch);

        if (isset($array['Result']) && $array['Result'] == '000') {
            $TransToken = $array['TransToken'];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://secure.3gdirectpay.com/API/v6/');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);

            $xml_charge = '<?xml version="1.0" encoding="utf-8"?>'
                . '<API3G>'
                . '<CompanyToken>' . $CompanyToken . '</CompanyToken>'
                . '<Request>chargeTokenCreditCard</Request>'
                . '<TransactionToken>' . $TransToken . '</TransactionToken>'
                . '<CreditCardNumber>' . $this->input->post('creditcardnumber') . '</CreditCardNumber>'
                . '<CreditCardExpiry>' . $this->input->post('creditcardexpiry') . '</CreditCardExpiry>'
                . '<CreditCardCVV>' . $this->input->post('creditcardcvv') . '</CreditCardCVV>'
                . '<CardHolderName>' . $this->input->post('cardholdername') . '</CardHolderName>'
                . '<ChargeType></ChargeType>'
                . '</API3G>';

            curl_setopt($ch, CURLOPT_POSTFIELDS, $xml_charge);
            $headers = array();
            $headers[] = 'Content-Type: application/xml';
            $headers[] = 'Accept: application/xml';
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            $result1 = curl_exec($ch);
            if (curl_errno($ch)) {
                $data['error'] = array('ResultExplanation' => curl_error($ch));
                curl_close($ch);
                $this->load->view('user/studentcourse/online_course/dpopay/guest_course/index', $data);
                return;
            }

            $xml2 = @simplexml_load_string($result1);
            if ($xml2 === false) {
                $data['error'] = array(
                    'ResultExplanation' => 'Invalid gateway response',
                    'message'           => substr($result1, 0, 500),
                );
                curl_close($ch);
                $this->load->view('user/studentcourse/online_course/dpopay/guest_course/index', $data);
                return;
            }

            $json = json_decode(json_encode($xml2), true);
            curl_close($ch);

            if (isset($json['ResultExplanation']) && $json['ResultExplanation'] == 'Transaction Charged') {
                $this->guest_success($array['TransRef'], $cart_data);
                return;
            } else {
                $data['error'] = $json;
                $this->load->view('user/studentcourse/online_course/dpopay/guest_course/index', $data);
                return;
            }
        } else {
            $data['error'] = $array;
            $this->load->view('user/studentcourse/online_course/dpopay/guest_course/index', $data);
            return;
        }
    }

    /**
     * Persist successful guest cart payment and send notifications
     *
     * @param string $TransRef
     * @param array  $cart_data
     */
    private function guest_success($TransRef, $cart_data)
    {
        if (empty($cart_data)) {
            redirect(base_url('students/online_course/course_payment/paymentfailed'));
            return;
        }

        foreach ($cart_data as $cart_data_value) {
            $payment_data = array(
                'date'                     => date('Y-m-d'),
                'guest_id'                 => $cart_data_value['guest_id'],
                'online_courses_id'        => $cart_data_value['id'],
                'course_name'              => $cart_data_value['name'],
                'actual_price'             => $cart_data_value['actual_amount'],
                'paid_amount'              => $cart_data_value['price'],
                'payment_type'             => 'Online',
                'transaction_id'           => $TransRef,
                'note'                     => "Online course fees deposit through DPO Txn ID: " . $TransRef,
                'payment_mode'             => 'DPO',
                'processing_charge_type'   => isset($cart_data_value['processing_charge_type']) ? $cart_data_value['processing_charge_type'] : null,
                'processing_charge_amount' => isset($cart_data_value['gateway_processing_charge']) ? $cart_data_value['gateway_processing_charge'] : 0,
            );
            $this->course_payment_model->add($payment_data);

            $sender_details = array(
                'email'            => $cart_data_value['email'],
                'courseid'         => $cart_data_value['id'],
                'class'            => null,
                'class_section_id' => null,
                'section'          => null,
                'title'            => $cart_data_value['name'],
                'price'            => $cart_data_value['price'],
                'discount'         => $cart_data_value['discount'],
                'assign_teacher'   => $cart_data_value['staff'],
                'purchase_date'    => $this->customlib->dateformat(date('Y-m-d')),
            );

            $this->course_mail_sms->purchasemail('online_course_purchase_for_guest_user', $sender_details);
        }

        redirect(base_url("students/online_course/course_payment/paymentsuccess"));
    }
}


