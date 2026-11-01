<?php
declare(strict_types=1);

final class TranscriptionProvider
{
    private static function request(string $url, array $headers, mixed $body, int $timeout=600): array
    {
        $ch=curl_init($url);
        curl_setopt_array($ch,[
            CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>$body,CURLOPT_RETURNTRANSFER=>true,
            CURLOPT_CONNECTTIMEOUT=>20,CURLOPT_TIMEOUT=>$timeout,CURLOPT_HTTPHEADER=>$headers
        ]);
        $raw=curl_exec($ch);$code=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);$err=curl_error($ch);curl_close($ch);
        if($raw===false)throw new RuntimeException('Transcription provider connection failed: '.$err);
        $json=json_decode($raw,true);
        if($code<200||$code>=300){$message=is_array($json)?($json['error']['message']??$json['message']??''):'';
            throw new RuntimeException('Transcription provider HTTP '.$code.($message?': '.substr((string)$message,0,350):''));
        }
        if(!is_array($json))throw new RuntimeException('Transcription provider returned an invalid JSON response.');
        return $json;
    }

    public static function upload(string $absoluteFile,string $language):array
    {
        $provider=strtolower((string)config('UPLOAD_ASR_PROVIDER','groq'));
        if($provider==='groq'){
            $key=(string)config('GROQ_API_KEY','');$url=(string)config('GROQ_ASR_URL','https://api.groq.com/openai/v1/audio/transcriptions');
            $model=(string)config('GROQ_ASR_MODEL','whisper-large-v3-turbo');$max=(int)config('ASR_MAX_FILE_MB',25);
        }elseif($provider==='openai'){
            $key=(string)config('OPENAI_API_KEY','');$url=(string)config('OPENAI_ASR_URL','https://api.openai.com/v1/audio/transcriptions');
            $model=(string)config('OPENAI_ASR_MODEL','gpt-transcribe');$max=(int)config('ASR_MAX_FILE_MB',100);
        }elseif($provider==='custom'){
            $key=(string)config('ASR_API_KEY','');$url=(string)config('ASR_API_URL','');$model=(string)config('ASR_MODEL','whisper-1');$max=(int)config('ASR_MAX_FILE_MB',100);
        }else throw new RuntimeException('Unsupported UPLOAD_ASR_PROVIDER. Choose groq, openai or custom.');
        if(!$key||!$url)throw new RuntimeException('Configure the API key and endpoint for '.$provider.' in .env.');
        if(!is_file($absoluteFile))throw new RuntimeException('Uploaded media file is missing from storage.');
        if(filesize($absoluteFile)>$max*1048576)throw new RuntimeException('This provider accepts files up to '.$max.' MB. Reduce the media size or configure a provider with a larger limit.');
        $format=$provider==='groq'?'verbose_json':'json';
        $post=['model'=>$model,'file'=>new CURLFile($absoluteFile),'response_format'=>$format];
        if($language!=='auto'){if($provider==='openai'&&$model==='gpt-transcribe')$post['languages[]']=$language;else $post['language']=$language;}
        if($provider!=='openai')$post['temperature']='0';
        $r=self::request($url,['Authorization: Bearer '.$key],$post,900);
        $text=(string)($r['text']??'');$segments=$r['segments']??[];
        if($text===''&&isset($r['transcript'])&&is_string($r['transcript']))$text=$r['transcript'];
        return ['text'=>$text,'segments'=>is_array($segments)?$segments:[],'raw'=>$r,'provider'=>$provider,'model'=>$model];
    }

    private static function localUrl(string $mediaUrl,string $language):array
    {
        if(!function_exists('proc_open'))throw new RuntimeException('PHP proc_open is disabled; use a compatible URL transcription API instead.');
        $python=(string)config('PYTHON_BIN','python3');$script=BASE_PATH.'/workers/url_transcribe.py';
        if(!is_file($script))throw new RuntimeException('Local URL transcription worker is missing.');
        $proc=proc_open([$python,$script],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,BASE_PATH);
        if(!is_resource($proc))throw new RuntimeException('Could not start the Python URL transcription worker.');
        fwrite($pipes[0],json_encode(['url'=>$mediaUrl,'language'=>$language],JSON_UNESCAPED_SLASHES));fclose($pipes[0]);
        $stdout=stream_get_contents($pipes[1]);fclose($pipes[1]);$stderr=stream_get_contents($pipes[2]);fclose($pipes[2]);$code=proc_close($proc);
        $result=json_decode((string)$stdout,true);
        if($code!==0||!is_array($result))throw new RuntimeException('Local URL transcription failed: '.substr(trim((string)$stderr)?:'worker returned invalid output',0,500));
        return $result;
    }

    public static function url(string $mediaUrl,string $platform,string $language):array
    {
        if(strtolower((string)config('URL_TRANSCRIPT_PROVIDER','compatible_api'))==='local_ytdlp')return self::localUrl($mediaUrl,$language);
        $base=rtrim((string)config('TRANSCRIPT_API_URL',''),' /');
        $token=(string)config('TRANSCRIPT_API_TOKEN','');
        if(!$base)throw new RuntimeException('Configure TRANSCRIPT_API_URL for public video and podcast URL transcription.');
        $allowed=SUPPORTED_PLATFORMS;
        if(!in_array($platform,$allowed,true))throw new RuntimeException('Unsupported source platform.');
        $body=json_encode(['url'=>$mediaUrl,'language'=>$language],JSON_UNESCAPED_SLASHES);
        $headers=['Content-Type: application/json'];if($token!=='')$headers[]='Authorization: Bearer '.$token;
        $r=self::request($base.'/api/'.$platform.'/transcript',$headers,$body,240);
        $data=$r['data']??$r;
        if(!is_array($data))$data=$r;
        $text=$data['text']??(is_string($data['transcript']??null)?$data['transcript']:'');
        $segments=$data['segments']??(is_array($data['transcript']??null)?$data['transcript']:[]);
        if(!$text&&is_array($segments)){$parts=[];foreach($segments as $segment)if(is_array($segment))$parts[]=$segment['text']??$segment['content']??'';$text=implode(' ',$parts);}
        return ['text'=>(string)$text,'segments'=>is_array($segments)?$segments:[],'raw'=>$r,'provider'=>(string)config('URL_TRANSCRIPT_PROVIDER','compatible_api'),'model'=>(string)config('URL_TRANSCRIPT_MODEL','platform-default')];
    }
}
