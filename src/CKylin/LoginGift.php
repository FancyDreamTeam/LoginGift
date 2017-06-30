<?php


namespace CKylin;

//COMMON Uses
use pocketmine\command\Command;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\utils\Config;
use pocketmine\Server;
use pocketmine\Player;
use pocketmine\command\CommandSender;
use pocketmine\event\player\PlayerJoinEvent;

use RVIP\RVIP;
use onebone\economyapi\EconomyAPI;

class LoginGift extends PluginBase implements Listener
{

    public function onEnable() {
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->path = $this->getDataFolder();
		@mkdir($this->path);
        $this->defaultsettings = array(
            'enabled'=>true,
            'checkByDevice'=>true,
            'signgift'=>[
                'everyday'=>[
                    'money'=>500,
                    'vip_day'=>0,
                    'vip_level'=>0,
                    ],
                1=>[
                    'money'=>1000,
                    'vip_day'=>0,
                    'vip_level'=>0,
                    // 'cards'=>false
                    ],
                7=>[
                    'money'=>5000,
                    'vip_day'=>1,
                    'vip_level'=>1,
                    // 'cards'=>false
                    ],
                30=>[
                    'money'=>10000,
                    'vip_day'=>5,
                    'vip_level'=>1,
                    // 'cards'=>false
                    ],
                ],
        );
		$this->cfg = new Config($this->path."options.yml", Config::YAML, $this->defaultsettings);
        $this->log = new Config($this->path."data.yml", Config::YAML, array())
		$this->getLogger()->info(TextFormat::GREEN . 'Enabled');
        $this->vip = RVIP::$RVIP;
        $this->eco = EconomyAPI::getInstance();
	}

	public function onDisable() {
		$this->saveall();
		$this->getLogger()->info(TextFormat::BLUE . 'Exited.');
	}

    public function onJoin(PlayerJoinEvent $e){
        $p = $e->getPlayer();
        $this->sign($p);
    }

    public function sign(Player $p){
        $p->sendMessage('---=<[每日签到]>=---')
        if(!$thi->hasSigned($p)){
            $this->signPlayer($p);
            $data = $this->getLog($p);
            $p->sendMessage('成功使用 '.$data['PName'].' 的身份签到！');
            $p->sendMessage('已经连续签到'.$data['days'].'天');
        }else{
            $data = $this->getLog($p);
            $p->sendMessage('您今天已经使用 '.$data['PName'].' 的身份签到过了。');
            $p->sendMessage('已经连续签到'.$data['days'].'天');
        }
    }

    public function getdate(){
        return ['y'=>date('Y'),'m'=>date('m'),'d'=>date('d')];
    }

    public function saveall(){
        $this->log->save();
        $this->cfg->save();
    }

    public function getcfg($settings){
        $result = '';
        if($this->cfg->exists($settings)){
            $result = $this->cfg->get($settings);
        }else{
            $result =  $this->defaultsettings[$settings];
            $this->cfg->set($settings,$result);
        }
        $this->saveall();
        return $result;
    }

    public function setcfg($settings,$value){
        $this->cfg->set($settings,$value);
        $this->saveall();
    }

    public function getKey(Player $p){
        if($this->getcfg('checkByDevice')){
            $key = $p->getClientId();
        }else{
            $key = $p->getName();
        }
        return $key;
    }

    public function hasSigned(Player $p){
        $key = $this->getKey($p);
        if($this->log->exists($key)){
            $day = $this->getdate();
            $cfg = $this->log->get($key);
            if(
                $cfg['Y']==$day['Y'] &&
                $cfg['m']==$day['m'] &&
                $cfg['d']==$day['d']
            ){
                return true;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    public function signPlayer(Player $p){
        $key = $this->getKey($p);
        if(!$this->log->exists($key)) $this->initLog($p);
        $cfg = $this->log->get($key);
        $date = $this->getdate();
        if($date['Y']==$cfg['Y'] && $date['m']==$cfg['m']){
            if($date['d']==1){
                $cfg['days'] = 1;
            }else{
                $lastday = $date['d']-1;
                if($lastday==$cfg['d']){
                    $cfg['days']++;
                }else{
                    $cfg['days'] = 1;
                }
            }
        }else{
            $cfg['days'] = 1;
        }
        $this->signgift($p,$cfg['days']);
        $cfg['PName'] = $p->getName();
        $cfg['Y'] = $date['Y'];
        $cfg['m'] = $date['m'];
        $cfg['d'] = $date['d'];
        $this->log->set($key,$cfg);
        $this->saveall();
    }

    public function getLog(Player $p){
        $key = $this->getLey($p);
        if(!$this->log->exists($key)) $this->initLog($p);
        return $this->log->get($key);
    }

    public function initLog(Player $p){
        $key = $this->getKey($p);
        $this->log->set($key,[
            'PName'=>'Steve',
            'days'=>0,
            'Y'=>0000,
            'm'=>00,
            'd'=>00
        ]);
        $this->saveall();
    }

    public function signgift($p,$days){
        $daygift = $this->getcfg('signgift');
        if(!empty($daygift[$days])){
            $gift = $daygift[$days];
        }else{
            $gift = $daygift['everyday'];
        }
        if((!empty($gift['vip_day'])) && (!empty($gift['vip_level']))){
            $this->vip->VIP('add',$p->getName(),$gift['vip_level'],$gift['vip_day']);
            $p->sendMessage('恭喜你获得VIP'.$gift['vip_level'].'的'.$gift['vip_day'].'天试用资格！');
        }
        if(!empty($gift['money'])){
            $this->eco->addMoney($p,$gift['money']);
            $p->sendMessage($gift['money'].'金币已经发放到你的账户');
        }
    }

}
