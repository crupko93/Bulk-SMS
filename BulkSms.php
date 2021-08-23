
/*********************************************************************************/
// Custom Bulk-SMS Class
/*********************************************************************************/

class BulkSms{
    
    private $options;
    private $url;
    private $pid;
    private $accountNumber;
    private $passwordPHASH;
    private $alias;
    
      
    
    public function set_global($alloptions){
        
        $this->options = $alloptions; // here we get all wp options
        $this->url = $this->options['url']; // then set global config for sms bulk api
        $this->pid = $this->options['pid']; // this values is saved in wp db
        $this->accountNumber = $this->options['acn']; // this values is saved in wp db  
        $this->passwordPHASH = $this->options['pswdPHASH']; // this values is saved in wp db
        $this->alias = $this->options['alias']; // this values is saved in wp db
        
    }
    
    public function get_data(){
        return $this->pid.' '.$this->passwordPHASH.' '.md5($this->pid.$this->passwordPHASH);
    }
    
    public function send($phone, $message){
        
        $phashKey = md5($this->pid.$this->passwordPHASH.$phone);    
        $URL = $this->url;
        $ar = array( 
            'PID' => $this->pid,
            'PHASH' => $phashKey,
            'DNIS' => $phone,
            'ANI' => '',
            'Alias' => $this->alias,
            'Enc' => 'UTF8',
            'BMess' => $message
        );
        $ar['BMess'] = str_replace("&", "%26", $ar['BMess']);
        $ar['BMess'] = str_replace("+", "%2B", $ar['BMess']);
        $ReqString = json_encode($ar);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "Request=".$ReqString);
        $data = curl_exec($ch);
        
        if ( ! curl_errno($ch) ) echo $data;
        else echo 'Curl error: ' . curl_error($ch);
        curl_close($ch);
        
    }

    
    public function get_balance(){

        $phashKey = md5($this->pid.time().$this->accountNumber.$this->passwordPHASH);    
        $URL = 'https://ext.123api.xyz/ED3B8F6F-A218-486B-871E-9654B1221DCA/Get_Balance.php';
        $ar = array( 
            'PID' => $this->pid,
            'UTS' => time(),
            'Account' => $this->accountNumber,
            'PHASH' => $phashKey,
        );
        
        $ReqString = json_encode($ar);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "Request=".$ReqString);
        $data = curl_exec($ch);
        curl_close($ch);
        if ( ! curl_errno($ch) && !empty($data) ){
            return json_decode($data, true);
        } 
        else{
            echo 'Curl error: ' . curl_error($ch);
        } 
        
       
    }


}
