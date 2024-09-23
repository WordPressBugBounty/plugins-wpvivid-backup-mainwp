<?php

class Mainwp_WPvivid_Connect_server
{
    private $url='https://pro.wpvivid.com/wc-api/wpvivid_api';
    private $update_url='http://download.wpvivid.com';
    private $public_key;

    public function __construct()
    {

    }

    public function get_mainwp_encrypt_token($user_info)
    {
        $public_key=$this->get_key();
        if($public_key===false)
        {
            $ret['result']='failed';
            $ret['error']='get public key failed.';
            return $ret;
        }

        $crypt=new Mainwp_WPvivid_crypt($public_key);
        $encrypt_user_info=$crypt->encrypt_user_token($user_info);
        $encrypt_user_info=base64_encode($encrypt_user_info);
        $ret['result']='success';
        $ret['token']=$encrypt_user_info;
        return $ret;
    }

    public function get_mainwp_status($user_info,$encrypt_user_info)
    {
        $public_key=$this->get_key();
        if($public_key===false)
        {
            $ret['result']='failed';
            $ret['error']='get public key failed.';
            return $ret;
        }

        $crypt=new Mainwp_WPvivid_crypt($public_key);

        if($encrypt_user_info)
        {
            $encrypt_user_info=$crypt->encrypt_user_token($user_info);
            $encrypt_user_info=base64_encode($encrypt_user_info);
        }
        else {
            $encrypt_user_info=$user_info;
        }

        $crypt->generate_key();

        $json['user_info'] = $encrypt_user_info;
        $json['domain'] = strtolower(home_url());
        $json=wp_json_encode($json);
        $data=$crypt->encrypt_message($json);

        $action='get_mainwp_dashboard_status';
        $url=$this->url;
        $url.='?request='.$action;
        $url.='&data='.rawurlencode(base64_encode($data));
        $options=array();
        $options['timeout']=30;
        $ret=$this->remote_request($url,$options);

        if($ret['result']=='success')
        {
            if($encrypt_user_info)
            {
                $ret['user_info']=$encrypt_user_info;
            }
            return $ret;
        }
        else
        {
            return $ret;
        }
    }

    public function mwp_wpvivid_get_site_url($site_id)
    {
        global $mainwp_wpvivid_extension_activator;
        $site_url = false;
        $websites=$mainwp_wpvivid_extension_activator->get_websites_ex();
        foreach ( $websites as $website ){
            if($site_id === $website['id']){
                $site_url = $website['url'];
                $site_url = rtrim($site_url, '/');
                break;
            }
        }
        return $site_url;
    }

    public function login($user_info,$site_id)
    {
        $site_url = $this->mwp_wpvivid_get_site_url($site_id);
        if($site_url === false){
            $ret['result']='failed';
            $ret['error']='Failed to get child site url.';
            return $ret;
        }

        $public_key=$this->get_key();
        if($public_key===false)
        {
            $ret['result']='failed';
            $ret['error']='get public key failed.';
            return $ret;
        }

        $crypt=new Mainwp_WPvivid_crypt($public_key);

        $encrypt_user_info=$user_info;

        $crypt->generate_key();

        $json['user_info'] = $encrypt_user_info;
        $json['domain'] = strtolower($site_url);
        $json=wp_json_encode($json);
        $data=$crypt->encrypt_message($json);

        $action='get_dashboard_status';
        $url=$this->url;
        $url.='?request='.$action;
        $url.='&data='.rawurlencode(base64_encode($data));
        $options=array();
        $options['timeout']=30;
        $ret=$this->remote_request($url,$options);

        if($ret['result']=='success')
        {
            return $ret;
        }
        else
        {
            return $ret;
        }
    }

    public function active_site($user_info,$site_id)
    {
        $site_url = $this->mwp_wpvivid_get_site_url($site_id);
        if($site_url === false){
            $ret['result']='failed';
            $ret['error']='Failed to get child site url.';
            return $ret;
        }

        $public_key=$this->get_key();
        if($public_key===false)
        {
            $ret['result']='failed';
            $ret['error']='get public key failed.';
            return $ret;
        }

        $crypt=new Mainwp_WPvivid_crypt($public_key);

        $encrypt_user_info=$user_info;

        $crypt->generate_key();

        $json['user_info'] = $encrypt_user_info;
        $json['domain'] = strtolower($site_url);
        $json=wp_json_encode($json);
        $data=$crypt->encrypt_message($json);

        //$action='active_dashboard_site';
        $action='active_mainwp_child_site';
        $url=$this->url;
        $url.='?request='.$action;
        $url.='&data='.rawurlencode(base64_encode($data));
        $options=array();
        $options['timeout']=30;
        $ret=$this->remote_request($url,$options);

        if($ret['result']=='success')
        {
            if($encrypt_user_info)
            {
                $ret['user_info']=$encrypt_user_info;
            }
            return $ret;
        }
        else
        {
            return $ret;
        }
    }

    public function get_dashboard_download_link($user_info,$addons)
    {
        $public_key=$this->get_key();
        if($public_key===false)
        {
            $ret['result']='failed';
            $ret['error']='get public key failed.';
            return $ret;
        }

        $crypt=new Mainwp_WPvivid_crypt($public_key);

        $crypt->generate_key();

        $json['user_info'] = $user_info;
        $json['mainwp_update']=1;
        $json['addons']=$addons;
        $json=wp_json_encode($json);
        $data=$crypt->encrypt_message($json);

        $url='https://update.wpvivid.com';
        $url.='?data='.rawurlencode(base64_encode($data));

        $ret['result']='success';
        $ret['download_link']=$url;
        return $ret;
    }

    public function get_staging_download_link($user_info, $addons)
    {
        $public_key=$this->get_key();
        if($public_key===false)
        {
            $ret['result']='failed';
            $ret['error']='get public key failed.';
            return $ret;
        }

        $crypt=new Mainwp_WPvivid_crypt($public_key);

        $crypt->generate_key();

        $json['user_info'] = $user_info;
        $json['mainwp_update']=1;
        $json['addons']=$addons;
        $json=wp_json_encode($json);
        $data=$crypt->encrypt_message($json);

        $url='https://update.wpvivid.com';
        $url.='?data='.rawurlencode(base64_encode($data));

        $ret['result']='success';
        $ret['download_link']=$url;
        return $ret;
    }

    public function get_key()
    {
        global $mainwp_wpvivid_extension_activator;
        $login_options = $mainwp_wpvivid_extension_activator->get_global_login_addon();
        if($login_options !== false)
        {
            if(isset($login_options['wpvivid_connect_key']) && !empty($login_options['wpvivid_connect_key']))
            {
                $public_key = $login_options['wpvivid_connect_key'];
            }
        }

        if(empty($public_key))
        {
            $options=array();
            $options['timeout']=30;
            $request=wp_remote_request($this->url.'?request=get_key',$options);

            if(!is_wp_error($request) && ($request['response']['code'] == 200))
            {
                $json= wp_remote_retrieve_body($request);
                $body=json_decode($json,true);
                if(is_null($body))
                {
                    return false;
                }

                if($body['result']=='success')
                {
                    $public_key=base64_decode($body['public_key']);
                    if($public_key==null)
                    {
                        return false;
                    }
                    else
                    {
                        $login_options['wpvivid_connect_key'] = $public_key;
                        $mainwp_wpvivid_extension_activator->set_global_login_addon($login_options);
                        return $public_key;
                    }
                }
                else
                {
                    return false;
                }
            }
            else
            {
                return false;
            }
        }
        else
        {
            return $public_key;
        }
    }

    public function get_token($license,$email,$pw)
    {
        $public_key=$this->get_key();
        if($public_key===false)
        {
            return false;
        }

        if(empty($license))
        {
            $crypt=new Mainwp_WPvivid_crypt($public_key);
            $encrypt_user_info=$crypt->encrypt_user_token($license);
            $encrypt_user_info=base64_encode($encrypt_user_info);
            return $encrypt_user_info;
        }
        else
        {
            $crypt=new Mainwp_WPvivid_crypt($public_key);
            $encrypt_user_info=$crypt->encrypt_user_info($email,$pw);
            $encrypt_user_info=base64_encode($encrypt_user_info);
            $crypt->generate_key();

            $json['user_info'] = $encrypt_user_info;
            $json=wp_json_encode($json);
            $data=$crypt->encrypt_message($json);

            $action='get_mainwp_token';
            $url=$this->url;
            $url.='?request='.$action;
            $url.='&data='.rawurlencode(base64_encode($data));
            $options=array();
            $options['timeout']=30;
            $ret=$this->remote_request($url,$options);

            if($ret['result']=='success')
            {
                $user_info['token']=$ret['token'];
                $encrypt_user_info=$crypt->encrypt_user_token($user_info);
                $encrypt_user_info=base64_encode($encrypt_user_info);
                return $encrypt_user_info;
            }
            else
            {
                return false;
            }
        }
    }

    public function remote_request($url,$body=array())
    {
        $options=array();
        $options['timeout']=30;
        if(empty($options['body']))
        {
            $options['body']=$body;
        }

        $retry=0;
        $max_retry=3;

        $ret['result']='failed';
        $ret['error']='remote request failed';

        while($retry<$max_retry)
        {
            $request=wp_remote_request($url,$options);

            if(!is_wp_error($request) && ($request['response']['code'] == 200))
            {
                $json= wp_remote_retrieve_body($request);
                $body=json_decode($json,true);

                if(is_null($body))
                {
                    $ret['result']='failed';
                    $ret['error']='Decoding json failed. Please try again later.';
                }

                if(isset($body['result'])&&$body['result']=='success')
                {
                    return $body;
                }
                else
                {
                    if(isset($body['result'])&&$body['result']=='failed')
                    {
                        $ret['result']='failed';
                        $ret['error']=$body['error'];
                        if(isset($body['error_code']))
                        {
                            $ret['error_code']=$body['error_code'];
                        }
                    }
                    else if(isset($body['error']))
                    {
                        $ret['result']='failed';
                        $ret['error']=$body['error'];
                        if(isset($body['error_code']))
                        {
                            $ret['error_code']=$body['error_code'];
                        }
                    }
                    else
                    {
                        $ret['result']='failed';
                        $ret['error']='login failed';
                        $ret['test']=$body;
                    }
                }
            }
            else
            {
                $ret['result']='failed';
                if ( is_wp_error( $request ) )
                {
                    $error_message = $request->get_error_message();
                    $ret['error']="Sorry, something went wrong: $error_message. Please try again later or contact us.";
                }
                else if($request['response']['code'] != 200)
                {
                    $ret['error']=$request['response']['message'];
                }
                else {
                    $ret['error']=$request;
                }
            }

            $retry++;
        }


        return $ret;
    }
}