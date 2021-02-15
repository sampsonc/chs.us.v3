<?php

namespace Hp;

//  PROJECT HONEY POT ADDRESS DISTRIBUTION SCRIPT
//  For more information visit: http://www.projecthoneypot.org/
//  Copyright (C) 2004-2021, Unspam Technologies, Inc.
//
//  This program is free software; you can redistribute it and/or modify
//  it under the terms of the GNU General Public License as published by
//  the Free Software Foundation; either version 2 of the License, or
//  (at your option) any later version.
//
//  This program is distributed in the hope that it will be useful,
//  but WITHOUT ANY WARRANTY; without even the implied warranty of
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//  GNU General Public License for more details.
//
//  You should have received a copy of the GNU General Public License
//  along with this program; if not, write to the Free Software
//  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA
//  02111-1307  USA
//
//  If you choose to modify or redistribute the software, you must
//  completely disconnect it from the Project Honey Pot Service, as
//  specified under the Terms of Service Use. These terms are available
//  here:
//
//  http://www.projecthoneypot.org/terms_of_service_use.php
//
//  The required modification to disconnect the software from the
//  Project Honey Pot Service is explained in the comments below. To find the
//  instructions, search for:  *** DISCONNECT INSTRUCTIONS ***
//
//  Generated On: Sun, 17 Jan 2021 10:29:37 -0500
//  For Domain: chs.us
//
//

//  *** DISCONNECT INSTRUCTIONS ***
//
//  You are free to modify or redistribute this software. However, if
//  you do so you must disconnect it from the Project Honey Pot Service.
//  To do this, you must delete the lines of code below located between the
//  *** START CUT HERE *** and *** FINISH CUT HERE *** comments. Under the
//  Terms of Service Use that you agreed to before downloading this software,
//  you may not recreate the deleted lines or modify this software to access
//  or otherwise connect to any Project Honey Pot server.
//
//  *** START CUT HERE ***

define('__REQUEST_HOST', 'hpr3.projecthoneypot.org');
define('__REQUEST_PORT', '80');
define('__REQUEST_SCRIPT', '/cgi/serve.php');

//  *** FINISH CUT HERE ***

interface Response
{
    public function getBody();
    public function getLines(): array;
}

class TextResponse implements Response
{
    private $content;

    public function __construct(string $content)
    {
        $this->content = $content;
    }

    public function getBody()
    {
        return $this->content;
    }

    public function getLines(): array
    {
        return explode("\n", $this->content);
    }
}

interface HttpClient
{
    public function request(string $method, string $url, array $headers = [], array $data = []): Response;
}

class ScriptClient implements HttpClient
{
    private $proxy;
    private $credentials;

    public function __construct(string $settings)
    {
        $this->readSettings($settings);
    }

    private function getAuthorityComponent(string $authority = null, string $tag = null)
    {
        if(is_null($authority)){
            return null;
        }
        if(!is_null($tag)){
            $authority .= ":$tag";
        }
        return $authority;
    }

    private function readSettings(string $file)
    {
        if(!is_file($file) || !is_readable($file)){
            return;
        }

        $stmts = file($file);

        $settings = array_reduce($stmts, function($c, $stmt){
            list($key, $val) = \array_pad(array_map('trim', explode(':', $stmt)), 2, null);
            $c[$key] = $val;
            return $c;
        }, []);

        $this->proxy       = $this->getAuthorityComponent($settings['proxy_host'], $settings['proxy_port']);
        $this->credentials = $this->getAuthorityComponent($settings['proxy_user'], $settings['proxy_pass']);
    }

    public function request(string $method, string $uri, array $headers = [], array $data = []): Response
    {
        $options = [
            'http' => [
                'method' => strtoupper($method),
                'header' => $headers + [$this->credentials ? 'Proxy-Authorization: Basic ' . base64_encode($this->credentials) : null],
                'proxy' => $this->proxy,
                'content' => http_build_query($data),
            ],
        ];

        $context = stream_context_create($options);
        $body = file_get_contents($uri, false, $context);

        if($body === false){
            trigger_error(
                "Unable to contact the Server. Are outbound connections disabled? " .
                "(If a proxy is required for outbound traffic, you may configure " .
                "the honey pot to use a proxy. For instructions, visit " .
                "http://www.projecthoneypot.org/settings_help.php)",
                E_USER_ERROR
            );
        }

        return new TextResponse($body);
    }
}

trait AliasingTrait
{
    private $aliases = [];

    public function searchAliases($search, array $aliases, array $collector = [], $parent = null): array
    {
        foreach($aliases as $alias => $value){
            if(is_array($value)){
                return $this->searchAliases($search, $value, $collector, $alias);
            }
            if($search === $value){
                $collector[] = $parent ?? $alias;
            }
        }

        return $collector;
    }

    public function getAliases($search): array
    {
        $aliases = $this->searchAliases($search, $this->aliases);
    
        return !empty($aliases) ? $aliases : [$search];
    }

    public function aliasMatch($alias, $key)
    {
        return $key === $alias;
    }

    public function setAlias($key, $alias)
    {
        $this->aliases[$alias] = $key;
    }

    public function setAliases(array $array)
    {
        array_walk($array, function($v, $k){
            $this->aliases[$k] = $v;
        });
    }
}

abstract class Data
{
    protected $key;
    protected $value;

    public function __construct($key, $value)
    {
        $this->key = $key;
        $this->value = $value;
    }

    public function key()
    {
        return $this->key;
    }

    public function value()
    {
        return $this->value;
    }
}

class DataCollection
{
    use AliasingTrait;

    private $data;

    public function __construct(Data ...$data)
    {
        $this->data = $data;
    }

    public function set(Data ...$data)
    {
        array_map(function(Data $data){
            $index = $this->getIndexByKey($data->key());
            if(is_null($index)){
                $this->data[] = $data;
            } else {
                $this->data[$index] = $data;
            }
        }, $data);
    }

    public function getByKey($key)
    {
        $key = $this->getIndexByKey($key);
        return !is_null($key) ? $this->data[$key] : null;
    }

    public function getValueByKey($key)
    {
        $data = $this->getByKey($key);
        return !is_null($data) ? $data->value() : null;
    }

    private function getIndexByKey($key)
    {
        $result = [];
        array_walk($this->data, function(Data $data, $index) use ($key, &$result){
            if($data->key() == $key){
                $result[] = $index;
            }
        });

        return !empty($result) ? reset($result) : null;
    }
}

interface Transcriber
{
    public function transcribe(array $data): DataCollection;
    public function canTranscribe($value): bool;
}

class StringData extends Data
{
    public function __construct($key, string $value)
    {
        parent::__construct($key, $value);
    }
}

class CompressedData extends Data
{
    public function __construct($key, string $value)
    {
        parent::__construct($key, $value);
    }

    public function value()
    {
        $url_decoded = base64_decode(str_replace(['-','_'],['+','/'],$this->value));
        if(substr(bin2hex($url_decoded), 0, 6) === '1f8b08'){
            return gzdecode($url_decoded);
        } else {
            return $this->value;
        }
    }
}

class FlagData extends Data
{
    private $data;

    public function setData($data)
    {
        $this->data = $data;
    }

    public function value()
    {
        return $this->value ? ($this->data ?? null) : null;
    }
}

class CallbackData extends Data
{
    private $arguments = [];

    public function __construct($key, callable $value)
    {
        parent::__construct($key, $value);
    }

    public function setArgument($pos, $param)
    {
        $this->arguments[$pos] = $param;
    }

    public function value()
    {
        ksort($this->arguments);
        return \call_user_func_array($this->value, $this->arguments);
    }
}

class DataFactory
{
    private $data;
    private $callbacks;

    private function setData(array $data, string $class, DataCollection $dc = null)
    {
        $dc = $dc ?? new DataCollection;
        array_walk($data, function($value, $key) use($dc, $class){
            $dc->set(new $class($key, $value));
        });
        return $dc;
    }

    public function setStaticData(array $data)
    {
        $this->data = $this->setData($data, StringData::class, $this->data);
    }

    public function setCompressedData(array $data)
    {
        $this->data = $this->setData($data, CompressedData::class, $this->data);
    }

    public function setCallbackData(array $data)
    {
        $this->callbacks = $this->setData($data, CallbackData::class, $this->callbacks);
    }

    public function fromSourceKey($sourceKey, $key, $value)
    {
        $keys = $this->data->getAliases($key);
        $key = reset($keys);
        $data = $this->data->getValueByKey($key);

        switch($sourceKey){
            case 'directives':
                $flag = new FlagData($key, $value);
                if(!is_null($data)){
                    $flag->setData($data);
                }
                return $flag;
            case 'email':
            case 'emailmethod':
                $callback = $this->callbacks->getByKey($key);
                if(!is_null($callback)){
                    $pos = array_search($sourceKey, ['email', 'emailmethod']);
                    $callback->setArgument($pos, $value);
                    $this->callbacks->set($callback);
                    return $callback;
                }
            default:
                return new StringData($key, $value);
        }
    }
}

class DataTranscriber implements Transcriber
{
    private $template;
    private $data;
    private $factory;

    private $transcribingMode = false;

    public function __construct(DataCollection $data, DataFactory $factory)
    {
        $this->data = $data;
        $this->factory = $factory;
    }

    public function canTranscribe($value): bool
    {
        if($value == '<BEGIN>'){
            $this->transcribingMode = true;
            return false;
        }

        if($value == '<END>'){
            $this->transcribingMode = false;
        }

        return $this->transcribingMode;
    }

    public function transcribe(array $body): DataCollection
    {
        $data = $this->collectData($this->data, $body);

        return $data;
    }

    public function collectData(DataCollection $collector, array $array, $parents = []): DataCollection
    {
        foreach($array as $key => $value){
            if($this->canTranscribe($value)){
                $value = $this->parse($key, $value, $parents);
                $parents[] = $key;
                if(is_array($value)){
                    $this->collectData($collector, $value, $parents);
                } else {
                    $data = $this->factory->fromSourceKey($parents[1], $key, $value);
                    if(!is_null($data->value())){
                        $collector->set($data);
                    }
                }
                array_pop($parents);
            }
        }
        return $collector;
    }

    public function parse($key, $value, $parents = [])
    {
        if(is_string($value)){
            if(key($parents) !== NULL){
                $keys = $this->data->getAliases($key);
                if(count($keys) > 1 || $keys[0] !== $key){
                    return \array_fill_keys($keys, $value);
                }
            }

            end($parents);
            if(key($parents) === NULL && false !== strpos($value, '=')){
                list($key, $value) = explode('=', $value, 2);
                return [$key => urldecode($value)];
            }

            if($key === 'directives'){
                return explode(',', $value);
            }

        }

        return $value;
    }
}

interface Template
{
    public function render(DataCollection $data): string;
}

class ArrayTemplate implements Template
{
    public $template;

    public function __construct(array $template = [])
    {
        $this->template = $template;
    }

    public function render(DataCollection $data): string
    {
        $output = array_reduce($this->template, function($output, $key) use($data){
            $output[] = $data->getValueByKey($key) ?? null;
            return $output;
        }, []);
        ksort($output);
        return implode("\n", array_filter($output));
    }
}

class Script
{
    private $client;
    private $transcriber;
    private $template;
    private $templateData;
    private $factory;

    public function __construct(HttpClient $client, Transcriber $transcriber, Template $template, DataCollection $templateData, DataFactory $factory)
    {
        $this->client = $client;
        $this->transcriber = $transcriber;
        $this->template = $template;
        $this->templateData = $templateData;
        $this->factory = $factory;
    }

    public static function run(string $host, int $port, string $script, string $settings = '')
    {
        $client = new ScriptClient($settings);

        $templateData = new DataCollection;
        $templateData->setAliases([
            'doctype'   => 0,
            'head1'     => 1,
            'robots'    => 8,
            'nocollect' => 9,
            'head2'     => 1,
            'top'       => 2,
            'legal'     => 3,
            'style'     => 5,
            'vanity'    => 6,
            'bottom'    => 7,
            'emailCallback' => ['email','emailmethod'],
        ]);

        $factory = new DataFactory;
        $factory->setStaticData([
            'doctype' => '<!DOCTYPE html>',
            'head1'   => '<html><head>',
            'head2'   => '<title>Nubile Verbal|chs.us</title></head>',
            'top'     => '<body><div align="center">',
            'bottom'  => '</div></body></html>',
        ]);
        $factory->setCompressedData([
            'robots'    => 'H4sIAAAAAAAAA7PJTS1JVMhLzE21VSrKT8ovKVZSSM7PK0nNK7FVystPLErOyCxLVbKzwa8uMy8ltUInLT8nJ79cyQ4ApuIax1UAAAA',
            'nocollect' => 'H4sIAAAAAAAAA7PJTS1JVMhLzE21VcrL103NTczM0U3Oz8lJTS7JzM9TUkjOzytJzSuxVdJXsgMAKsBXli0AAAA',
            'legal'     => 'H4sIAAAAAAAAA7VbbW_bOBL-fr-CSA7ZFnDTOG3zcsoWcBOn8aJ1crbToh9libZ5K4s-SkrW--uPmhmKI1lR9rBpAdeWLZHDeXnmmSFzkYfzRIpIJkm2CSOVLn_dO9qD600Yx-56rk0sTfnx4z8uclP-F3-8WOg0F1m-TeSve-XnN4twrZLtv0SkC6Ok6Ym1TnU5rgz2Ph6k82wTiIs5PRjpRJtf96_h38fNxdvy248Xb-d058XcsE-zlRTle67FwX7_uA9f6nwly_fvcp6V70aGcfke2luO3gXl__0ghcdWcg0D7vf7R4FO3_DH8NUqVr4jVrUMLt10I6PyfVyOb6eE4bONAmk2Ri9BqqL89STQbs7hOlTl-ygXCoU7PwWhz6yE5RerMIOJslyXj76HuR7CpHBL-RDk2sDYSyP_zlLaHli5B2Cyk_MgjFY4F4r9au-3SpCTgOyTFXRTGsN1KfjRcdA2vqLxF9Xa0y3qdf_sXSDNAxlOuIU1pG4VesFWWbfRD13ASOtw2_ZgzFYbzss1nQbgHd9UpioJ9s9OgtF0RJ-PgsFkBva__zSFe9zkNjxePkYqEbuMRet2hjkP5Bw9BaxRCAwUkWNwlBcql8ydKaTQqJkAZz0UMzBqbsKUnjjYf38S1A3zlNjL1tCGccIEZr4p1iFNamUymR0cfd2E6wxthvIwQ5RRIiDQj-DWTGAEyEPxA2xY4kT_LAgZIMge3NGlP71ww38I0mXbnbIWFqXAa5n-HRf1z21C40Eqy5-bPVY2as5RKUYulR8oCR8BeaSB5UgL3lWQRTncqCJ5iL6uH_6aHX9_0o4RwR7YKtxsyBBtg2Q0yAzB3FQAPLu1AXUcQBj9ELcTAMh_3wPwvGBoccxjvl_M_wNCmzUi7lYXbdJHTPtrEYFvLUQOwSEFqheccYGe4VG7oeKGGEYSmpcXqfiG_ufvn2v0f_9NLEFzUZgkbpDY4KT4g16mFPEgVvk5QVTj0R6mPoIo_ssv3gcSwVfBYLkI0-4FoHiYmUVIwN2ZhUpPScQA0xu6Z4aiwmdt1pLugeF0Wv54xgS1eI5qD5fdskkrhAMBaw1EQJtWRkwNy7ql8NvB54m_mA7H5cV41hUan36epy6MBgYTtcKCEwAcaKkfPLqHEYCE_8LIBSlOgVEjmWVV0rOgynjELp5NOWgZFXm3zBRjN-jNPaQ1apm6lHJchgRLHJnEcFN_SqBK0e9E0CAOdOtSuQe91QZZh0QvQUEtr3PgfBSIMrSOj1gQ_kVwa-jfEixYheOcFkY1Yis6MSoYMlL_OODzg5YtHcLMq4ull9QBJ9oFKWqHkOGOkE9htW7JMZY6A1DptURmAKBhowOW0RmtaBU30HAoZrjAk7NgNP5cfp4MB5cv6Pz_V5XAHBXWtQK304TLGNkpOvum1aVCHj1JsoXEGKsHllENQjJCTccQj8qHBLPm3dAq6zQYcXsojQnTZIcCXUT-ARMDOev3a2NY_MsZSK_A3R8TDJuE0gcMF6OkGdwex8iKMpvoYP2oDekp4dO4mZECDQFFuvcafovXpMtEeVTJFY7MvgEwZzxzlWOQZs7l0vzwGYfvrllK0HpAWVYS16kWCqOUrNCRw3M3xs1gxjLi4GD_9Cz4gZ5zehyI2-ufB-rRSmwRi7FM8nZfMjaxkplkAqa-AI6LMEHVIo6uNKb43OsVdDv4wiPm45dd6klABKOMqT6WJuvxO0p5Ij6ypQdYlJOnLGswnK8q5mHUHHJOliFt-SUT6JYJreUZnH_aScOcT8nxNFuhblw4GIotQG2aVzzuPAty2go3ZQvRgq-6XZDWWsGtYav9gxqVbKMqQ5gqKg58M5h8ewpgXIL6Cox46seb3Qx_PvCGFW1nnmO5ZYiIqUGPlkl4XGFsso-UDr8Za59MXpWVXh91uKTaslaFMhceXVoCxiYPBcLzDXr8mnmB0RVVtNymxweyOOgBqQyrrnoVMBmXmIp76Eog1GF35lCM4B2KU-ti4G0bo1d1RynD7-6Oi55ukQugImSEaUfMkfDm6sE_HC5BdSrHnGZy1xASSNNhGYmMGsqaDC9v-YyFIbaLrRoKCT9NkSqEodwLD242c62Od5YNg59NKfn_XX-zUg4nX2t8MooQHRQqZvlsZ4oC09ZsYKQ3NwUwqMeVFn61VSVWkjG_ZPnHpqG06bAGklCOcvFqDLdWpOmcA6WRYW3kjgWE1LOgRuAS6kDyThzsnx9siPCyhxpqiLuUSclXcI3ACNQcpWOJ4OOX0eWg5hQqB6kVIRKOssR2QJmRLGFBiiuxFktF7r3uUaGvqDWy_NzotKFQ0ODVdJespblk9VZHa3CDtd1_Mca9JVEhiuyOtrZZ-3OP7P7-KJgiWQEfezF4LGMZZrqqOYbEXmekU1C-61lSbS0iFKpscIpvdCMYxvovpH_DgZMpcDi1KnwfTGpF1y8EAYqxxCpDnwcGVBL3RB0TOtqJERrOFtrxc8mHeGdrZ8dx8x-adOAwzrqhTkNaJ3wpY078Pn0ZPsXnQ8TGR8UcTxosJ9M8jPAdFSLFAF0_fZb1QS_hHJ1bRSJrqN0yPg4E32rYLXOa7RUMtRWYeRzRLl-fB2C2m_Lz7JZqJbLQ6XlwLV4IP7sS9eUt-GmzHWzBH2mHovLV7YFY11XeXta5wF46YQkd6ZEo-HLKcsuohv5GlzM7-bva5ImKsMGGAyJmPCaMHuj1pjZQpmJ4IjasWmkt31zCRtwthOtvoD8n9m64zkRT0C-D7y1NaYLDru4rtR1iMd8-KVVc1c4WjUCdc5n7wtsWUUjZAWm9io-CFNM5QeTDjnYhG49qTOzO6JwG2wOBIuRPWSYW7p7hBIwyLj_fDIVP7EPAadjaeCHHvBpBAIx2ywzeaEsz7AU90-Xmmw3WaT17LJvHXa0Pe0tPhOALKTXAc0w8R02ghbgct2QqasUTnke485b1sJGSSKH5QIbygOtqRQhaWOoYorS1Lm1C1M1tXjCRWJ6pi2R9D5hLaBRjcDS6cwAp0bumVIXDzFS315QIQNhYRwmBFfupYPC-Rv0RQIHyjjDNYZ_LFVhWyio327Rckeer4Rjw0NL5wxfyNkS6duNZRRN5mVFfm-nMxt1JsK2ookWlV755Y3Mz5uiVNjtq9vB3HKBnMd1d3052ZbHDYZ6QPd6Mfqg226yfLGu8kVo12jcULVt0wh6dB6EC7Rvqfzunc9UJGmjZ5GS_WUt8CCa10DQasbnGu91GkkesgXUg_u522pwyrIVzVoU9Mga6EJumlhpse9CCFTGyKRxnwZpxW2z92zqqGnV2OwFEG12PhtUsL-Bb10_HQNqZjAD2QU0CW28PtI_sQnRFHePvCACYXj06UnHvNu5Ki6usyVTGt-M3jQrK58p5kVOxyLKsdeCaJeyqwUK2buVlJysljKfdVKpnBXKGXhcNxHADf1B_4sPxDupal7kaXfIVyBhBym137BY9qZhWJK50OUCbAuluLg-rCLEBBW0Ar1JN9WVNBADZjhINkzOPFsJvZLJm7zVGIfYCZJhVUDqmcmQibDH-ci45bWHLe4RLZt2aEvlJDgqnFEDDuhdo2ZJAFl6ase01HQGoWFFNd8fB_dda5Ga8RMTdPKpouzbC55BTWY-M9_E8mpejIVxbaoi-yRvftjhBxA6pKiaEZYgftqqn2Wmk9c1Gt7WO01LMdzOBq_VCCrK9SlTaWdYpNW_3z84Das9iBYEN1RWqbSf_f7qf4t73Di4a5Ax4oQ6ePF_D1ZvbqgmC5tCrs6wsB0AHx9TneRkP5bLSVlzZ53iGquH9r8g7USnu8BAsQFDAPSrMKFNs9C9Es9k9rldvo0YNBJiLFaTOqRWLrcdcprubNyrNWcpfPEkG6ELRhnxOyfQADp6ADkSdyjdYHXrGcrWD_Vkekju_bw8jR5YXGkMCQ9neBo9FtMtEkSJY0wBEC03cIZWJK4JS0kQ_Ujtvd32bOz7k9BorDACc-9kLpmfP0T8EtMFF_sM3BcJ06y-mqNRViG0ksJWl94CC5kHRfssOH56OZrWG6pwq5EQhF9qKnRMaNoPxQRzV78LnVYGJ_qn7XHH_XVYN7q7bIkyhW4GlfY8Ipk7CRsgQRe0K0bnfdHIpwWppgeFTw3ysBbw23D6sO4ax4LerdCnaZIkPcVe9yPAwka1va9qcjMY2_aA8U4Eo9pI-tfda-PZuHKOxu84zrRFCHqjN0RWnc8oYv7NCUAp_9Os8SHdqiklLeWOf2SBsxdRJwK7EmnXx3ZgnQeF2w6ANCVzPNbRxZ1hhRICL2EoRm7rEFnezP0eLrIg4JLss5ZmoJMD1EOTO5eLG5wnuuuHZXnIbepx0RYfWmq9USDoUgzWxVXjLVt3slu_vXl7-HACiEsepKqX-zWt0p0PXIea7LsgMmNI6TssuWkmKF-A4SLZ0QrpHc603TsVnp23HejaIUCpTTezi_MsVlBl1dG3IwrbBbubF10psQo6mlqulCJmsIMxpvx51pLIYHZjObJYXNvt5MNQp6bhtQlsIVzV839IumM0Wj7UYGnTVajNo1Q7vgBMNfoZ79IMHPAzu-gA10nCXYOtSEXoyjqGr_t3Olqip-LxP6H130gtoV8H3WVzKcq-NNFg7GdZepX3v3o6ILpWioVTWJorJt5U_R9IlidAPH9NJEkXhLNOigQt2JbDgIo1bV-vMmiuEM5llh_x3aHFOrsqP48uhd5cXMqU8qE5LIqmAwMAojtvkfepVj0ZIrdU6arp9y07-d70WbDC3R0kbch29aVXvhppWUZtdBxZU46t2Db-FvxF5C39cYj_Y3_8HHpfidWkyAAA',
            'style'     => 'H4sIAAAAAAAAAyXMywmAMAwA0FUEr9bPtRWP3SPGFAolKWkERdzdg2-Atza7C20wViXUs9Kugg9KEfV9jDEkYfO7lKNb5np1oBnK0ICba6Q5BaPL3EEoCpaFPQtTeNfpfz_ktb8KXwAAAA',
            'vanity'    => 'H4sIAAAAAAAAA22SwU4jMQyGX8VKr7RTClup6XSEtipCSNAK2APHTJLOZAlx5Lgd-vabGcqFRZElW4m__4-TklXtLWjrfYpKu9CsxFT0ZVTGnMsayVjqs8Qnb1eiVvqtITwEI0eLxWLZOcOtnF1N48dSVCVTDgNH5V0TVoIxfjWeoRIu4wfMcvzKcZ27PiXG5JqWZULvzHBktF6ve2L2FuDM2GNgWaM30OuBIqf8RVIhjZMlt19q9EhyNJ_Pl1lZ9p4iJscOgyTrFbujzcybsuipVVmw-c8unHNv9yzgm_mrrDrN6_rztgpasvuVaJmjLIqu6yaR8K_V3GKwp4g8QWoKAdqrlDKGrKZDtDWhFtXD5uH35gm2t7B72t5v1i9wt33cvMJu-1IWqipr-lHhELL594nGd_Ej9jnvwp2io01sCXaEnA3lEcCj5Q7prQdnm0dnrIH6BH8G4CA5DKToH7EYfkf1DwFyad0lAgAA',
        ]);
        $factory->setCallbackData([
            'emailCallback' => function($email, $style = null){
                $value = $email;
                $display = 'style="display:' . ['none',' none'][random_int(0,1)] . '"';
                $style = $style ?? random_int(0,5);
                $props[] = "href=\"mailto:$email\"";
        
                $wrap = function($value, $style) use($display){
                    switch($style){
                        case 2: return "<!-- $value -->";
                        case 4: return "<span $display>$value</span>";
                        case 5:
                            $id = 'k5chusig';
                            return "<div id=\"$id\">$value</div>\n<script>document.getElementById('$id').innerHTML = '';</script>";
                        default: return $value;
                    }
                };
        
                switch($style){
                    case 0: $value = ''; break;
                    case 3: $value = $wrap($email, 2); break;
                    case 1: $props[] = $display; break;
                }
        
                $props = implode(' ', $props);
                $link = "<a $props>$value</a>";
        
                return $wrap($link, $style);
            }
        ]);

        $transcriber = new DataTranscriber($templateData, $factory);

        $template = new ArrayTemplate([
            'doctype',
            'injDocType',
            'head1',
            'injHead1HTMLMsg',
            'robots',
            'injRobotHTMLMsg',
            'nocollect',
            'injNoCollectHTMLMsg',
            'head2',
            'injHead2HTMLMsg',
            'top',
            'injTopHTMLMsg',
            'actMsg',
            'errMsg',
            'customMsg',
            'legal',
            'injLegalHTMLMsg',
            'altLegalMsg',
            'emailCallback',
            'injEmailHTMLMsg',
            'style',
            'injStyleHTMLMsg',
            'vanity',
            'injVanityHTMLMsg',
            'altVanityMsg',
            'bottom',
            'injBottomHTMLMsg',
        ]);

        $hp = new Script($client, $transcriber, $template, $templateData, $factory);
        $hp->handle($host, $port, $script);
    }

    public function handle($host, $port, $script)
    {
        $data = [
            'tag1' => '181be223c87edc53b9cb4a1d995f557b',
            'tag2' => '9a940d9bfaa64d91bb7c74835bfe1a8f',
            'tag3' => '3649d4e9bcfd3422fb4f9d22ae0a2a91',
            'tag4' => md5_file(__FILE__),
            'version' => "php-".phpversion(),
            'ip'      => $_SERVER['REMOTE_ADDR'],
            'svrn'    => $_SERVER['SERVER_NAME'],
            'svp'     => $_SERVER['SERVER_PORT'],
            'sn'      => $_SERVER['SCRIPT_NAME']     ?? '',
            'svip'    => $_SERVER['SERVER_ADDR']     ?? '',
            'rquri'   => $_SERVER['REQUEST_URI']     ?? '',
            'phpself' => $_SERVER['PHP_SELF']        ?? '',
            'ref'     => $_SERVER['HTTP_REFERER']    ?? '',
            'uagnt'   => $_SERVER['HTTP_USER_AGENT'] ?? '',
        ];

        $headers = [
            "User-Agent: PHPot {$data['tag2']}",
            "Content-Type: application/x-www-form-urlencoded",
            "Cache-Control: no-store, no-cache",
            "Accept: */*",
            "Pragma: no-cache",
        ];

        $subResponse = $this->client->request("POST", "http://$host:$port/$script", $headers, $data);
        $data = $this->transcriber->transcribe($subResponse->getLines());
        $response = new TextResponse($this->template->render($data));

        $this->serve($response);
    }

    public function serve(Response $response)
    {
        header("Cache-Control: no-store, no-cache");
        header("Pragma: no-cache");

        print $response->getBody();
    }
}

Script::run(__REQUEST_HOST, __REQUEST_PORT, __REQUEST_SCRIPT, __DIR__ . '/phpot_settings.php');

