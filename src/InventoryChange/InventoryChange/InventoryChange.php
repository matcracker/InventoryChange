<?php
// This plugin is made by matcracker
namespace InventoryChange\InventoryChange;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\utils\Config;
use pocketmine\item\Item;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerGameModeChangeEvent;
class InventoryChange extends PluginBase implements Listener{

	public function onEnable(){
		$this->loadYml();
		$this->saveYml();
		$this->gmc = [];
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

 	public function onPlayerMove(PlayerMoveEvent $event){
		$p = $event->getPlayer();
		if($p->isCreative()) return;
		$n = strtolower($p->getName());
 		$wn = strtolower($event->getTo()->getLevel()->getFolderName());
		$this->createInv($p,$wn);
		$ic = $this->ic[$n];
		$icw = $ic["Worlds"];
 		$icl = $ic["LastWorld"];
		$inv = $p->getInventory();
 		if(isset($this->gmc[$n])){
 			foreach($this->gmc[$n] as $k => $i){
				$this->gmc[$n][$k] = Item::get(...(explode(":",$i)));
			}
			$inv->setContents($this->gmc[$n]);
			unset($this->gmc[$n]);
 			$change = true;
 		}
		if($icl !== $wn){
			$icw[$icl] = [];
			if(!isset($icw[$wn])) $icw[$wn] = [];
			foreach($inv->getContents() as $i){
				if($i->getID() !== 0 and $i->getCount() > 0) $icw[$icl][] = $i->getID().":".$i->getDamage().":".$i->getCount();
			}
			foreach($icw[$wn] as $k => $i){
				$icw[$wn][$k] = Item::get(...(explode(":",$i)));
			}
			$inv->setContents($icw[$wn]);
			$icw[$wn] = [];
 			$this->ic[$n] = ["LastWorld" => $wn, "Worlds" => $icw];
			$this->saveYml();
			$p->sendMessage("[InventoryChange] " . ($this->LangItalian() ? "Il tuo inventario è cambiato": "Your inventory is change") . " : You Change World");
		}
	}

 	public function onPlayerGameModeChange(PlayerGameModeChangeEvent $event){
 		if($event->isCancelled()) return;
		$p = $event->getPlayer();
		$n = strtolower($p->getName());
		$g = $event->getNewGamemode();
 		$wn = strtolower($p->getLevel()->getFolderName());
		$this->createInv($p,$wn);
		$icw = $this->ic[$n]["Worlds"][$wn];
		$g = $event->getNewGamemode();
		if($g == 1){
			$inv = $p->getInventory();
			foreach($inv->getContents() as $i){
				if($i->getID() !== 0 and $i->getCount() > 0) $icw[] = $i->getID().":".$i->getDamage().":".$i->getCount();
			}
			$inv->clearAll();
 		}else{
 			$this->gmc[$n] = $icw;
 			$icw = [];
		}
		$this->ic[$n]["Worlds"][$wn] = $icw;
		$this->saveYml();
		$p->sendMessage("[InventoryChange] " . ($this->LangItalian() ? "Il tuo inventario è cambiato": "Your inventory is change ") . " : You change Game Mode");
	}

	public function createInv($p,$wn){
		$n = strtolower($p->getName());
		$change = false;
		if(!isset($this->ic[$n])){
			$this->ic[$n] = ["LastWorld" => strtolower($p->getLevel()->getFolderName()), "Worlds" => []];
			$change = true;
 		}
		$ic = $this->ic[$n];
		if(!isset($ic["Worlds"])){
			$ic["Worlds"] = [];
			$change = true;
		}
		$icw = $ic["Worlds"];
		if(!isset($icw[$wn])){
			$icw[$wn] = [];
			$change = true;
		}
		if(!isset($ic["LastWorld"])){
			$ic["LastWorld"] = $wn;
			$change = true;
		}
 		$icl = $ic["LastWorld"];
		if($change){
 			$this->ic[$n] = ["LastWorld" => $wn, "Worlds" => $icw];
			$this->saveYml();
		}		
		return $change;
	}

	public function loadYml(){
		@mkdir($this->getServer()->getDataPath() . "/plugins/InventoryChange/");
		$this->InventoryChange = new Config($this->getServer()->getDataPath() . "/plugins/InventoryChange/" . "InventoryChange.yml", Config::YAML);
		$this->ic = $this->InventoryChange->getAll();
	}

	public function saveYml(){
		foreach($this->ic as $wk => $ic){
			if(!isset($ic["Worlds"]) || !isset($ic["LastWorld"])){
				unset($this->ic[$wk]);
			}else{
				foreach($ic["Worlds"] as $k => $v){
					ksort($v);
					$this->ic[$k] = $v;
				}
			}
		}
		ksort($this->ic);
		$this->InventoryChange->setAll($this->ic);
		$this->InventoryChange->save();
		$this->loadYml();
	}
	public function LangItalian(){
		return (new Config($this->getServer()->getDataPath() . "/plugins/InventoryChange/" . "Italian.yml", Config::YAML, ["Italian" => false ]))->get("Italian");
	}
}