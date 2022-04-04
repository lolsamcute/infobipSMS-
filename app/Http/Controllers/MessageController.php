<?php

namespace App\Http\Controllers;

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

    public function upload(Request $request)
    {


        $location = 'uploads'; //Created an "uploads" folder for that
        // This is th location path of the message.csv file
        if ($filepath = public_path($location . "/" . "message.csv") {

        // Reading file
        $file = fopen($filepath, "r");
        $importData_arr = array(); // Read through the file and store the contents as an array
        $i = 0;
        //Read the contents of the uploaded file
        while (($filedata = fgetcsv($file, 1000, ",")) !== FALSE) {
        $num = count($filedata);
        // Skip first row (Remove below comment if you want to skip the first row)
        if ($i == 0) {
        $i++;
        continue;
        }
        for ($c = 0; $c < $num; $c++) {
        $importData_arr[$i][] = $filedata[$c];
        }
        $i++;
        }
        fclose($file); //Close after reading
        $j = 0;
        foreach ($importData_arr as $importData) {
        $name = $importData[1]; //Get user names
        $email = $importData[3]; //Get the user emails
        $j++;
        try {
        DB::beginTransaction();
        Player::create([
        'name' => $importData[1],
        'club' => $importData[2],
        'email' => $importData[3],
        'position' => $importData[4],
        'age' => $importData[5],
        'salary' => $importData[6]
        ]);
        //Send Email
        $this->sendEmail($email, $name);
        DB::commit();
        } catch (\Exception $e) {
        //throw $th;
        DB::rollBack();
        }
        }
        return response()->json([
        'message' => "$j records successfully uploaded"
        ]);
        } else {
        //no file was uploaded
        throw new \Exception('No file was uploaded', Response::HTTP_BAD_REQUEST);
        }
    }
}
