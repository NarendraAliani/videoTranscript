<?php
declare(strict_types=1);
final class AIService
{
    static function run(string $task,string $text,string $lang):string
    {
        $tasks=['summary'=>'Summarise with key takeaways.','chapters'=>'Create chapters without inventing timestamps.','translate'=>'Translate into '.$lang.'.','linkedin'=>'Write a LinkedIn post.','blog'=>'Write a blog article.','newsletter'=>'Write a newsletter.','twitter'=>'Write an X thread.','instagram'=>'Write an Instagram caption.','youtube'=>'Write a YouTube description.','reddit'=>'Write a Reddit post.','tiktok'=>'Write a short video script.'];
        if(!isset($tasks[$task]))throw new InvalidArgumentException('Unknown AI task.');
        $provider=strtolower((string)config('AI_PROVIDER','groq'));
        if($provider==='groq'){$key=(string)config('GROQ_API_KEY','');$url=(string)config('LLM_API_URL','https://api.groq.com/openai/v1/chat/completions');$model=(string)config('GROQ_CHAT_MODEL','openai/gpt-oss-20b');}
        else{$key=(string)config('LLM_API_KEY','');$url=(string)config('LLM_API_URL','');$model=(string)config('LLM_MODEL','');}
        if(!$url||!$key||!$model)throw new RuntimeException('Configure AI_PROVIDER and its API key/model in .env.');
        $body=['model'=>$model,'messages'=>[['role'=>'system','content'=>'Be accurate. Do not invent facts or timestamps. If source transcript is incomplete, say so.'],['role'=>'user','content'=>$tasks[$task]."\nUse only this transcript:\n".mb_substr($text,0,90000)]]];
        $ch=curl_init($url);curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>json_encode($body),CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>20,CURLOPT_TIMEOUT=>180,CURLOPT_HTTPHEADER=>['Content-Type: application/json','Authorization: Bearer '.$key]]);
        $raw=curl_exec($ch);$code=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);$err=curl_error($ch);curl_close($ch);
        if($raw===false)throw new RuntimeException('AI provider connection failed: '.$err);
        $r=json_decode($raw,true)?:[];if($code<200||$code>=300)throw new RuntimeException('AI provider HTTP '.$code.': '.substr((string)($r['error']['message']??''),0,300));
        $out=$r['choices'][0]['message']['content']??'';if(is_array($out))$out=implode("\n",array_map(fn($x)=>$x['text']??'', $out));
        if(!is_string($out)||trim($out)==='')throw new RuntimeException('AI provider returned empty content.');
        return $out;
    }
}
