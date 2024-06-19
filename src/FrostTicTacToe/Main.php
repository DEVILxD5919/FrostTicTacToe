<?php

declare(strict_types=1);

namespace FrostTicTacToe;

use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerChatEvent;
use function strtolower;

class Main extends PluginBase implements Listener {

    private array $games = [];
    private array $waiting = [];

    public function onEnable(): void {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
    if (!$sender instanceof Player) {
        $sender->sendMessage("§cThis command can only be used in-game.");
        return true;
    }

    if (count($args) < 1) {
        return false;
    }

    switch (strtolower($args[0])) {
        case "start":
            if (isset($this->waiting[$sender->getName()])) {
                $sender->sendMessage("§7[§aFrostTicTacToe§7] §eYou are already waiting for a player to join.");
                return true;
            }
            $this->waiting[$sender->getName()] = true;
            $sender->sendMessage("§7[§aFrostTicTacToe§7] §aYou have started a FrostTicTacToe game. Waiting for a player to join...");
            Server::getInstance()->broadcastMessage("§7[§aFrostTicTacToe§7] §b" . $sender->getName() . " §fis waiting for players to become FrostTicTacToe opponents! Type §e/ttt join " . $sender->getName() . " §fto join the board");
            break;

        case "join":
            if (isset($this->games[$sender->getName()])) {
                $sender->sendMessage("§7[§aFrostTicTacToe§7] §cYou are already in a game.");
                return true;
            }
            if (isset($this->waiting[$sender->getName()])) {
                $sender->sendMessage("§7[§aFrostTicTacToe§7] §cYou have already started a game. Wait for someone to join.");
                return true;
            }
            if (count($args) < 2) {
                $sender->sendMessage("§7[§aFrostTicTacToe§7] §fUsage: /FrostTicTacToe join <playerName>");
                return true;
            }
            $opponentName = $args[1];
            if (!isset($this->waiting[$opponentName])) {
                $sender->sendMessage("§7[§aFrostTicTacToe§7] §cThat player is not waiting for a game.");
                return true;
            }
            $opponent = $this->getServer()->getPlayerByPrefix($opponentName);
            if ($opponent === null || !$opponent->isOnline()) {
                $sender->sendMessage("§7[§aFrostTicTacToe§7] §cThat player is not online.");
                return true;
            }
            $game = new Game($opponent, $sender, $this);
            $this->games[$opponent->getName()] = $game;
            $this->games[$sender->getName()] = $game;
            unset($this->waiting[$opponent->getName()]);
            $game->start();
            break;

        case "help":
            $sender->sendMessage("§e------§7[§aFrostTicTacToe Help§7]§e------\n§bHow To Play:\n  §a- Type the numbers in the chat according to the numbers on the board\n\n§bCommand List:\n  §a- /ttt start §7» Start FrostTicTacToe Game and waiting player to join\n  §a- /ttt join <playerName> §7» Join the FrostTicTacToe game from the player who started the game\n  §a- /ttt list §7» List player waiting for players to enter the game\n  §a- /ttt end §7» Exit the game or end the game without winning\n");
            break;

        case "list":
            if (empty($this->waiting)) {
                $sender->sendMessage("§7[§aFrostTicTacToe§7] §cNo players are currently waiting for a game.");
            } else {
                $sender->sendMessage("§7[§aFrostTicTacToe§7] §ePlayers waiting for a game:");
                foreach ($this->waiting as $playerName => $value) {
                    $sender->sendMessage("§b - " . $playerName);
                }
            }
            break;

        case "end":
            if (!isset($this->games[$sender->getName()])) {
                $sender->sendMessage("§7[§aFrostTicTacToe§7] §cYou are not currently in a game.");
                return true;
            }
            $this->games[$sender->getName()]->end();
            break;

        default:
            return false;
    }

    return true;
}

    public function onPlayerQuit(PlayerQuitEvent $event): void {
        $player = $event->getPlayer();
        if (isset($this->games[$player->getName()])) {
            $this->games[$player->getName()]->end();
        }
        unset($this->waiting[$player->getName()]);
    }
    
    public function onPlayerChat(PlayerChatEvent $event): void {
        $player = $event->getPlayer();
        $message = $event->getMessage();

        if (!isset($this->games[$player->getName()])) {
            return;
        }

        $game = $this->games[$player->getName()];

        if (is_numeric($message) && strlen($message) === 1) {
            $position = (int)$message;
            $game->handleMove($player, $position);
            $event->cancel(); // Prevent the chat message from being broadcast
        }
    }

    public function removeGame(Game $game): void {
        foreach ($game->getPlayers() as $player) {
            unset($this->games[$player->getName()]);
        }
    }
}
