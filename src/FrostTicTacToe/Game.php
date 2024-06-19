<?php

declare(strict_types=1);

namespace FrostTicTacToe;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class Game {

    private Player $playerX;
    private Player $playerO;
    private array $board = [];
    private ?Player $currentPlayer = null;
    private Main $plugin;

    public function __construct(Player $playerX, Player $playerO, Main $plugin) {
        $this->playerX = $playerX;
        $this->playerO = $playerO;
        $this->plugin = $plugin;
        $this->board = array_fill(0, 9, " ");
        $this->currentPlayer = $playerX;
    }

    public function start(): void {
        $this->sendBoard();
        $this->playerX->sendMessage("§7[§aFrostTicTacToe§7] §2Game started! You are X. It's your turn.");
        $this->playerO->sendMessage("§7[§aFrostTicTacToe§7] §2Game started! You are O. Waiting for the other player's move.");
    }

    public function handleMove(Player $player, int $position): void {
        if ($player !== $this->currentPlayer) {
            $player->sendMessage("§7[§aFrostTicTacToe§7] §cIt's not your turn!");
            return;
        }

        if ($position < 1 || $position > 9 || $this->board[$position - 1] !== " ") {
            $player->sendMessage("§7[§aFrostTicTacToe§7] §cInvalid move!");
            return;
        }

        $this->board[$position - 1] = $player === $this->playerX ? "X" : "O";
        if ($this->checkWin()) {
            $this->end($player);
            return;
        }

        if (!in_array(" ", $this->board, true)) {
            $this->end(null);
            return;
        }

        $this->currentPlayer = $this->currentPlayer === $this->playerX ? $this->playerO : $this->playerX;
        $this->sendBoard();
        $this->currentPlayer->sendMessage("§7[§aFrostTicTacToe§7] §fIt's your turn!");
        ($this->currentPlayer === $this->playerX ? $this->playerO : $this->playerX)->sendMessage("§7[§aFrostTicTacToe§7] §eWaiting for the other player's move.");
    }

    private function sendBoard(): void {
        $board = $this->formatBoard();
        $this->playerX->sendMessage($board);
        $this->playerO->sendMessage($board);
    }

    private function formatBoard(): string {
        $displayBoard = array_map(function ($value, $index) {
            if ($value === "X") {
                return TextFormat::RED . "X" . TextFormat::RESET;
            } elseif ($value === "O") {
                return TextFormat::WHITE . "O" . TextFormat::RESET;
            } elseif ($value === " ") {
                return TextFormat::AQUA . ($index + 1) . TextFormat::RESET;
            }
            return $value;
        }, $this->board, array_keys($this->board));

        return TextFormat::AQUA . "FrostTicTacToe\n" .
            $displayBoard[0] . " | " . $displayBoard[1] . " | " . $displayBoard[2] . "\n" .
            "─+─+─\n" .
            $displayBoard[3] . " | " . $displayBoard[4] . " | " . $displayBoard[5] . "\n" .
            "─+─+─\n" .
            $displayBoard[6] . " | " . $displayBoard[7] . " | " . $displayBoard[8] . "\n\n";
    }

    private function checkWin(): bool {
        $winningCombinations = [
            [0, 1, 2], [3, 4, 5], [6, 7, 8], // Rows
            [0, 3, 6], [1, 4, 7], [2, 5, 8], // Columns
            [0, 4, 8], [2, 4, 6]             // Diagonals
        ];

        foreach ($winningCombinations as $combination) {
            if ($this->board[$combination[0]] !== " " &&
                $this->board[$combination[0]] === $this->board[$combination[1]] &&
                $this->board[$combination[1]] === $this->board[$combination[2]]) {
                return true;
            }
        }

        return false;
    }

    public function end(?Player $winner = null): void {
        if ($winner !== null) {
             $winner->sendMessage("§7[§aFrostTicTacToe§7] §2Congratulations! You won the game!");
            ($winner === $this->playerX ? $this->playerO : $this->playerX)->sendMessage("§7[§aFrostTicTacToe§7] §cYou lost the game.");
        } else {
            $this->playerX->sendMessage("§7[§aFrostTicTacToe§7] §eThe game ended in a draw.");
            $this->playerO->sendMessage("§7[§aFrostTicTacToe§7] §eThe game ended in a draw.");
        }

        $this->playerX->sendMessage("§7[§aFrostTicTacToe§7] §7Use /FrostTicTacToe start to start a new game.");
        $this->playerO->sendMessage("§7[§aFrostTicTacToe§7] §7Use /FrostTicTacToe start to start a new game.");

        $this->plugin->removeGame($this);
    }

    public function getPlayers(): array {
        return [$this->playerX, $this->playerO];
    }
}
