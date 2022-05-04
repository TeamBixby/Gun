<?php

declare(strict_types=1);

namespace TeamBixby\Gun;

use pocketmine\plugin\{Plugin, PluginBase};
use pocketmine\event\{Listener, HandlerList};
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\scheduler\{ClosureTask, TaskScheduler, TaskHandler, Task};
use pocketmine\nbt\tag\{ByteTag, CompoundTag, DoubleTag, FloatTag, StringTag, ListTag, ShortTag, IntTag};
use pocketmine\item\{VanillaItems, ItemFactory, Item, ItemIds};
use pocketmine\inventory\CreativeInventory;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use pocketmine\math\Vector3;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\Utils;
use TeamBixby\Gun\Gun;
use TeamBixby\Gun\form\GunMainForm;
use TeamBixby\Gun\command\GunCommand;
use TeamBixby\Gun\session\Session;
use Closure;
use function array_map;
use function array_values;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function str_replace;
use function yaml_emit;
use function yaml_parse;

class GunPlugin extends PluginBase implements Listener{
	use SingletonTrait;

	/** @var Gun[] */
	protected array $guns = [];
	/** @var Session[] */
	protected array $sessions = [];

	public function onLoad(): void{
		self::setInstance($this);
	}

	public function onEnable(): void{
		$this->saveDefaultConfig();
		$this->saveResource("config.yml");
		$this->msgconfig = new Config($this->getDataFolder() . 'config.yml', Config::YAML);
		$this->gunconfig = new Config($this->getDataFolder() . 'guns.yml', Config::YAML);
		if(file_exists($file = $this->getDataFolder() . "guns.yml")){
			$data = yaml_parse(file_get_contents($file));
			foreach($data as $name => $gunData){
				$gun = new Gun($name, Item::jsonDeserialize($gunData["item"]), $gunData["damage"], $gunData["ammo"], $gunData["scope"], $gunData["reloadCooldown"], $gunData["distance"], $gunData["canPassWall"]);
				$this->guns[$gun->getName()] = $gun;
			}
		}
		$itemFactory = ItemFactory::getInstance();
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->getServer()->getCommandMap()->register("gun", new GunCommand());
		$this->getScheduler()->scheduleRepeatingTask(new ClosureTask(function(): void{
			foreach($this->sessions as $name => $session){
				$session->check();
				$session->syncScope();
			}
		}), 10);
	}

	public function onDisable(): void{
		$res = [];
		foreach($this->guns as $name => $gun){
			$res[$gun->getName()] = $gun->jsonSerialize();
		}
		file_put_contents($this->getDataFolder() . "guns.yml", yaml_emit($res));
	}

	public function registerGun(Gun $gun) : void{
		$this->guns[$gun->getName()] = $gun;
	}

	public function unregisterGun(Gun $gun) : void{
		unset($this->guns[$gun->getName()]);
	}

	public function getGun(string $name) : ?Gun{
		return $this->guns[$name] ?? null;
	}

	public function getGunByItem(Item $item) : ?Gun{
		if(!$this->isGun($item)){
			return null;
		}
		$gunTag = $item->getNamedTag("gun");
		return $this->getGun($gunTag->getValue());
	}

	public function isGun(Item $item) : bool{
		if(($gunTag = $item->getNamedTag("gun")) === null){
			return false;
		}
		if($this->getGun($gunTag->getValue()) === null){
			return false;
		}
		return true;
	}

	/**
	 * @return Gun[]
	 */
	public function getGuns() : array{
		return array_values($this->guns);
	}

	public function createSession(Player $player) : Session{
		return $this->sessions[$player->getName()] = new Session($player);
	}

	public function getSession(Player $player) : ?Session{
		return $this->sessions[$player->getName()] ?? null;
	}

	public function getSessionNonNull(Player $player) : Session{
		return $this->getSession($player) ?? $this->createSession($player);
	}

	public function removeSession(Player $player) : void{
		unset($this->sessions[$player->getName()]);
	}

	public function onPlayerInteract(PlayerInteractEvent $event): void{
		$item = $event->getItem();
		$player = $event->getPlayer();
		$session = $this->getSessionNonNull($player);

		if(!$this->isGun($item)){
			return;
		}

		if($session->getNowGun() === null){
			return;
		}
		$session->useGun();
	}

	public function onPlayerItemHeld(PlayerItemHeldEvent $event): void{
		$player = $event->getPlayer();
		$item = $event->getItem();
		$session = $this->getSessionNonNull($player);

		$gun = $this->getGunByItem($item);
		$session->setNowGun($gun);
	}

	public function onPlayerJoin(PlayerJoinEvent $event): void{
		$player = $event->getPlayer();
		$this->createSession($player);
	}

	public function onPlayerQuit(PlayerQuitEvent $event): void{
		if($this->getSession($event->getPlayer()) !== null){
			$this->removeSession($event->getPlayer());
		}
	}

	public function designGun(Gun $gun) : Item{
		$item = $gun->getItem();
		$lores = array_values($this->msgconfig->get("message.gunLore"));
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
		$name = str_replace("%gun%", $gun->getName(), $this->msgconfig->get("message.gunName"));
		$item->setCustomName($name);
		$item->setNamedTag(new StringTag("gun", (string) $gun->getName()));
		return $item;
	}
}