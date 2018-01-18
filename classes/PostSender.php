<?php

/**
 * ����� ������������ ������ � ������ �������.
 *
 * � ��� ����� ����� ���� URL.
 * ����� ����, ������ ������������� ������ �� �����������, ������� ���� �������� � ������ ������. ������ ��.
 *
**/
class PostSender
{
    private $partnerURL = '';
    private $authFields = array ();
    
    /**
     * ��������� ������������� ������� �������� � ���� ��������,
     * ��� ������ ��������, ���������� URL � ������ �����������,
     * ���� ���������� � ������, ������� ����� �������� �������.
     *
     * @param   $partnerURL string  URL � ���������� �������
     * @param   $authFields array   ���� ����-��������, ������� ���� �������� � ����-������, ����� ������ ��������� ������.
     * @param   $formsPath  array   ����
    **/
    public function __construct ($partnerURL, $authFields)
    {
        // ��������� ������� ������.
        if (! parse_url ($partnerURL))
        {
            throw new Exception ('������ URL: ' . $partnerURL);
        }
        if (! is_array ($authFields))
        {
            throw new Exception ('authFields ������ ���� ��������');
        }
        
        $this->partnerURL = $partnerURL;
        
        // ���� ��� ������ ������ ��� �����������, ������ ��� ���� �� ����� "access denied" � ����� �� ��������.
        $response = $this->postForm (false, false);
        if (empty ($response))
        {
            throw new Exception ('������ �� ��������: ' . $partnerURL);
        }
        
        // ������ (��������) ������������ ��������� � ������� ������. - ��� � ��� ���� ����� �������
        // $this->authFields = \Utils::postSerializeFields ($authFields);
        $this->authFields = $authFields;
    }
    
    /**
     * ��������� ���������� POST-������.
     * 
     * ������ ������ ���� ��� ������� ������������.
     * ����������� ����������� ����� ������ �������������,
     * ���� ��������� ��������������� ����.
     * ���������� ���� ������, ���� �� �������, false �����.
     *
     * @param   $fields     array       ����-������
     * @param   $authorize  bool        ��������� �� ���� �����������
     * @return              bool|string ���������� ������ ��� false, ���� ������ �� ����.
    **/
    private function postForm ($fields, $authorize = true)
    {
        // ����� �������, ��� ������� ������ ���������� ������� �������� �� ��������.
        if (! is_array ($fields)) $fields = false;
        
        $curl = curl_init ($this->partnerURL);
        if (! $curl)
        {
            throw new Exception ('cURL �� ���� ���������� ���������� � ' . $this->partnerURL);
        }
        curl_setopt ($curl, CURLOPT_POST, true);
        if ($fields)
        {
            if ($authorize)
            {
                /**
                 * ���� �� ����� ������, ������������� �����������, ������,
                 * ���������� ������� ����� ������. ������� ������ ��, ���� ���.
                **/
                $fields += $this->authFields;
            }
            if (! curl_setopt ($curl, CURLOPT_POSTFIELDS, $fields))
            {
                throw new Exception ('�� ������� ��������� ���� POST');
            }
        }
        curl_setopt ($curl, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec ($curl);
        curl_close ($curl);
        
        return $result;
    }
    
    public function genericRequest ($RequestName, $fields, $Language)
    {
        return $this->postForm (array (
            'RequestName' => $RequestName,
        ) + $fields + array (
            'Language'    => $Language,
        ));
    }
}

?>
