<?php
declare(strict_types=1);final class TranscriptService{
static function link(int $uid,string $url,string $lang):int{if(!validate_media_url($url))throw new InvalidArgumentException('Unsupported media URL.');$p=platform_from_url($url);$d=db();$d->beginTransaction();try{$d->prepare("INSERT INTO transcripts(user_id,source_type,platform,source_url,language,status,created_at,updated_at) VALUES(?,'url',?,?,?,'queued',NOW(),NOW())")->execute([$uid,$p,$url,$lang]);$id=(int)$d->lastInsertId();$d->prepare("INSERT INTO jobs(user_id,transcript_id,job_type,payload,status,available_at,created_at,updated_at) VALUES(?,?,'url',?,'queued',NOW(),NOW(),NOW())")->execute([$uid,$id,json_encode(['url'=>$url,'platform'=>$p,'language'=>$lang])]);$d->prepare("INSERT INTO usage_ledger(user_id,transcript_id,units,reason,created_at) VALUES(?,?,1,'transcript request',NOW())")->execute([$uid,$id]);$d->commit();return $id;}catch(Throwable $e){$d->rollBack();throw $e;}}
static function upload(int $uid,array $f,string $lang):int{if(($f['error']??4)!==0)throw new RuntimeException('Upload failed.');if($f['size']>(int)config('UPLOAD_MAX_MB',25)*1048576)throw new RuntimeException('File too large.');$mime=(new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);if(!str_starts_with((string)$mime,'audio/')&&!str_starts_with((string)$mime,'video/'))throw new RuntimeException('Invalid audio/video file.');$dir=BASE_PATH.'/'.trim((string)config('UPLOAD_DIR','storage/uploads'),'/');if(!is_dir($dir))mkdir($dir,0750,true);$ext=preg_replace('/[^a-z0-9]/i','',pathinfo($f['name'],PATHINFO_EXTENSION));$name=bin2hex(random_bytes(20)).'.'.$ext;if(!move_uploaded_file($f['tmp_name'],$dir.'/'.$name))throw new RuntimeException('Could not store upload.');$rel=trim((string)config('UPLOAD_DIR','storage/uploads'),'/').'/'.$name;$d=db();$d->beginTransaction();try{$d->prepare("INSERT INTO transcripts(user_id,source_type,platform,source_file,original_filename,language,status,created_at,updated_at) VALUES(?,'upload','file',?,?,?,'queued',NOW(),NOW())")->execute([$uid,$rel,substr($f['name'],0,255),$lang]);$id=(int)$d->lastInsertId();$d->prepare("INSERT INTO jobs(user_id,transcript_id,job_type,payload,status,available_at,created_at,updated_at) VALUES(?,?,'upload',?,'queued',NOW(),NOW(),NOW())")->execute([$uid,$id,json_encode(['file'=>$rel,'language'=>$lang])]);$d->prepare("INSERT INTO usage_ledger(user_id,transcript_id,units,reason,created_at) VALUES(?,?,1,'upload request',NOW())")->execute([$uid,$id]);$d->commit();return $id;}catch(Throwable $e){$d->rollBack();@unlink(BASE_PATH.'/'.$rel);throw $e;}}
static function process(array $j):void
{
    $d=db();$p=json_decode($j['payload'],true)?:[];$started=microtime(true);
    $d->prepare("UPDATE transcripts SET status='processing',updated_at=NOW() WHERE id=?")->execute([$j['transcript_id']]);
    $provider=null;$model=null;$audioSeconds=null;$cost=null;
    try{
        $result=$j['job_type']==='url'
            ? TranscriptionProvider::url((string)$p['url'],(string)$p['platform'],(string)$p['language'])
            : TranscriptionProvider::upload(BASE_PATH.'/'.$p['file'],(string)$p['language']);
        $text=trim((string)($result['text']??''));$segments=$result['segments']??[];
        if($text==='')throw new RuntimeException('Provider returned an empty transcript.');
        $provider=(string)$result['provider'];$model=(string)$result['model'];
        $audioSeconds=isset($result['raw']['duration'])?(int)ceil((float)$result['raw']['duration']):null;
        if($audioSeconds===null&&is_array($segments)&&$segments){$last=end($segments);if(is_array($last))$audioSeconds=(int)ceil((float)($last['end']??$last['end_time']??0));}
        if($provider==='groq'&&$audioSeconds!==null)$cost=round(($audioSeconds/3600)*0.04,8);
        elseif($provider==='openai'&&$audioSeconds!==null)$cost=round(($audioSeconds/60)*(str_starts_with($model,'gpt-4o-mini')?0.003:0.006),8);
        $d->beginTransaction();
        $d->prepare("UPDATE transcripts SET transcript_text=?,provider_payload=?,provider_name=?,provider_model=?,duration_seconds=COALESCE(?,duration_seconds),status='completed',completed_at=NOW(),updated_at=NOW() WHERE id=?")->execute([$text,json_encode($result['raw']??[]),$provider,$model,$audioSeconds,$j['transcript_id']]);
        $d->prepare('DELETE FROM transcript_segments WHERE transcript_id=?')->execute([$j['transcript_id']]);
        $insert=$d->prepare('INSERT INTO transcript_segments(transcript_id,sequence_no,start_seconds,end_seconds,content) VALUES(?,?,?,?,?)');
        foreach($segments as $i=>$v){if(!is_array($v))continue;$content=trim((string)($v['text']??$v['content']??''));if($content==='')continue;$insert->execute([$j['transcript_id'],$i+1,max(0,(float)($v['start']??$v['start_time']??0)),max(0,(float)($v['end']??$v['end_time']??0)),$content]);}
        $d->prepare("INSERT INTO provider_runs(transcript_id,provider_name,model_name,audio_seconds,cost_usd,processing_ms,status,created_at) VALUES(?,?,?,?,?,?, 'completed',NOW())")->execute([$j['transcript_id'],$provider,$model,$audioSeconds,$cost,(int)((microtime(true)-$started)*1000)]);
        $d->prepare("UPDATE jobs SET status='completed',finished_at=NOW(),updated_at=NOW() WHERE id=?")->execute([$j['id']]);
        $d->commit();
    }catch(Throwable $e){
        if($d->inTransaction())$d->rollBack();
        app_log('Job '.$j['id'].': '.$e->getMessage());
        $message=substr($e->getMessage(),0,1500);
        $attempt=(int)($j['attempts']??0)+1;
        $retryable=(bool)preg_match('/HTTP (429|5[0-9]{2})|connection failed|timed out|temporarily unavailable/i',$message);
        if($retryable&&$attempt<3){
            $d->prepare("UPDATE jobs SET status='queued',available_at=DATE_ADD(NOW(),INTERVAL 60 SECOND),error_message=?,updated_at=NOW() WHERE id=?")->execute([$message,$j['id']]);
            $d->prepare("UPDATE transcripts SET status='queued',error_message=?,provider_name=?,provider_model=?,updated_at=NOW() WHERE id=?")->execute([$message,$provider,$model,$j['transcript_id']]);
        }else{
            $d->prepare("UPDATE jobs SET status='failed',error_message=?,finished_at=NOW(),updated_at=NOW() WHERE id=?")->execute([$message,$j['id']]);
            $d->prepare("UPDATE transcripts SET status='failed',error_message=?,provider_name=?,provider_model=?,updated_at=NOW() WHERE id=?")->execute([$message,$provider,$model,$j['transcript_id']]);
        }
        $d->prepare("INSERT INTO provider_runs(transcript_id,provider_name,model_name,processing_ms,status,error_message,created_at) VALUES(?,?,?,?, 'failed',?,NOW())")->execute([$j['transcript_id'],$provider??'unresolved',$model,(int)((microtime(true)-$started)*1000),substr($e->getMessage(),0,1500)]);
    }
}
}
