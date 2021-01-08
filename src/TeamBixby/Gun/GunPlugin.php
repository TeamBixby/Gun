<?php

declare(strict_types=1);

namespace TeamBixby\Gun;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\Item;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;

use function array_map;
use function array_values;
use function file_exists;
use function file_get_contents;
use function str_replace;
use function yaml_parse;

class GunPlugin extends PluginBase implements Listener{
	use SingletonTrait;
	/** @var Gun[] */
	protected $guns = [];

	public function onLoad() : void{
		self::setInstance($this);
	}

	public function onEnable() : void{
		if(file_exists($file = $this->getDataFolder() . "guns.yml")){
			$data = yaml_parse(file_get_contents($file));
			foreach($data as $name => $gunData){
				$gun = new Gun($name, Item::jsonDeserialize($gunData["item"]), $gunData["damage"], $gunData["ammo"], $gunData["scope"], $gunData["reloadCooldown"], $gunData["distance"], $gunData["canPassWall"]);
				$this->guns[$gun->getName()] = $gun;
			}
		}
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onPlayerInteract(PlayerInteractEvent $event) : void{
		$item = $event->getItem();

	}

	public function designGun(Gun $gun) : Item{
		$item = $gun->getItem();
		$lores = array_values($this->getConfig()->get("message.gunLore"));
		$item->setLore(array_map(function(string $line) use ($gun) : string{
			return str_replace(["%gun%", "%damage%", "%ammo%", "%scope%", "%cooldown%", "%distance%", "%passwall%"], [
				$gun->getName(),
				$gun->getDamage(),
				$gun->getAmmo(),
				$gun->getScope() !== -1 ? $gun->getScope() : "No scope provided",
				$gun->getReloadCooldown(),
				$gun->getDistance(),
				$gun->canPassWall() ? "true" : "false"
			], $line);
		}, $lores));
		$name = str_replace("%gun%", $gun->getName(), $this->getConfig()->get("message.gunName"));
		$item->setCustomName($name);
		return $item;
	}
}