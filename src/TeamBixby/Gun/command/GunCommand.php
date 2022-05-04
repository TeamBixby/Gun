<?php

declare(strict_types=1);

namespace TeamBixby\Gun\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\plugin\Plugin;
use TeamBixby\Gun\form\GunMainForm;
use TeamBixby\Gun\GunPlugin;
use TeamBixby\Gun\Gun;

class GunCommand extends PluginCommand{

	public function __construct(){
		parent::__construct("gun", GunPlugin::getInstance());
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
		if(!$this->testPermission($sender)){
			return false;
		}
		if(!$sender instanceof Player){
			return false;
		}
		$sender->sendForm(new GunMainForm());
		return true;
	}
}