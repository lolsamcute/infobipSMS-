<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

use GuzzleHttp\Client;
use Infobip\Api\SendSmsApi;
use Infobip\Configuration;
use Infobip\Model\SmsAdvancedTextualRequest;
use Infobip\Model\SmsDestination;
use Infobip\Model\SmsTextualMessage;

class MessageController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    function sendSmsFrom()
    {
        $location = 'uploads';
        $filename = "message.csv";
        $file = public_path($location . "/" . $filename); // Public Path Location where Message.csv is Located.

        if (! file_exists($file) ) {
            throw new \RuntimeException("The given file [$file] does not exist."); // Throw File Error if Not Exist
        }

        // open the file for Reading .
        $stream = fopen($file, 'r');
        $rows = [];
        $index = 0;

        // get table rows from the csv file
        while( ($row = fgetcsv($stream, 1000, ',')) !== false) {
            if ($index === 0) {
                $index++;
                // we skip the first row because
                // we are assuming it is the column header
                continue;
            }

            // generate random id and add to the 3rd column
            $row[2] = Str::orderedUuid();
            $rows[] = $row;

            $index++;
        }

        fclose($stream);

        // make concurrent request calls to api endpoint
        // to send sms

        $SENDER = "InfoSMS";
        $RECIPIENT = '234'.substr("08109844175", 1);
        $MESSAGE_TEXT = "Congratulations! Message Sent";

        foreach($rows as &$row) {

        $key = $row[1]; // we used the random generated id as key

        $BASE_URL = env('API_BASE_URL');
        $API_KEY = env('API_KEY');

        $configuration = (new Configuration())
        ->setHost($BASE_URL)
        ->setApiKeyPrefix('Authorization', 'App')
        ->setApiKey('Authorization', $API_KEY);

        $client = new Client();

        $sendSmsApi = new SendSMSApi($client, $configuration);
        $destination = (new SmsDestination())->setTo($RECIPIENT);
        $message = (new SmsTextualMessage())
            ->setFrom($SENDER)
            ->setText($MESSAGE_TEXT)
            ->setDestinations([$destination]);

        $request = (new SmsAdvancedTextualRequest())->setMessages([$message]);

        try {
            $smsResponse = $sendSmsApi->sendSmsMessage($request);
            // update the description row with response from Infobip API
            // $key = $row[2];



            // echo ("Response body: " . $smsResponse);
        } catch (Throwable $apiException) {
            echo("HTTP Code: " . $apiException->getCode() . "\n");

            echo ("Response body: " . $apiException->getResponseBody() . "\n");
        }
     }

        // override file with update csv using Write Method
        $stream = fopen($file, 'w');

        // add csv column headers
        fputcsv($stream, [ 'SenderId', 'MSISDN', 'MessageId', 'Description' ]);

        // update the file with updated csv rows
        foreach ($rows as $row) {
            fputcsv($stream, $row);
        }

        fclose($stream);

        return view('successful');
    }



}
