# AlternativeCoreWars
代替 - 核 - 戦争

## イベント一覧
| イベント             | 説明                |
|------------------|-------------------|
| GameEndEvent     | ゲームが終了した時のイベント    |
| GameFinishEvent  | ゲームの後片付けを行うイベント   |
| GameStartEvent   | ゲームが始まった時のイベント    |
| NexusDamageEvent | ネクサスが破壊されたときのイベント |
| PhaseStartEvent  | フェーズが始まった時のイベント   |

## 仕様
### ServerSpecificationNormalizer
#### サーバーの設定ファイル編集
- オートセーブ無効化
- サーバーデフォルトゲームモードをアドベンチャーに設定
- プレイヤーデータの保存を無効化
- RandomTickSpeedを0へ変更
#### コマンドの変更
- コマンド `ban`, `ban-ip`, `banlist`, `pardon`, `pardon-ip`, `defaultgamemode`, `save-all`, `save-on`, `save-off`, `setworldspawn`, `spawnpoint` を無効化
- コマンド `op`, `dumpmemory` の実行権限をコンソールのみに変更
#### 既存のブロック、アイテムの置き換え
##### ブロック
| ブロック | 変更内容                                                                  |
|------|-----------------------------------------------------------------------|
| 草    | 種のドロップ率変更 (6.25% → 0.662%)                                            |
| 葉っぱ  | リンゴのドロップ率変更 (0.5% → 5%)<br/>苗木のドロップ率変更 (5% → 2.5%)<br/>棒をドロップさせる (2%) |
| 小麦   | 完全に成長した時の種のドロップ数を変更 (0~4 → 0~1)                                       |
| 砂利   | 火打石のドロップ率変更 (10% → 25%)<br/>糸をドロップ (1.25%)                            |

### NoDeathScreenSystem
- スペクテイターの自殺と奈落ダメージの無効化
- プレイヤーが死んだ場合 `PlayerDeathEvent` の代わりに `PlayerDeathWihtoutDeathScreenEvent` を呼び出す
- `PlayerRespawnEvent` は基本的には呼び出されない

### SoulboundItemMonitor
- `InventoryTransactionEvent` を監視して `SlotChangeAction`, `DropItemAction` の二つを偽のアクションへ置き換える
  - `DropItemAction` には勝手に偽のインベントリを追加して、許可されていないインベントリでの操作に見せかけている
- プレイヤー死亡時にドロップするアイテムから除去
- 万が一アイテムがドロップしてしまった場合は即座にデスポーンさせる

### Lobby
- ロビーワールドの難易度をピースフルへ変更
- アイテムの使用を監視 → ゲームへの参加などを行う
- ロビーでの全てのダメージを無効化
- ロビーでのブロックの設置や破壊などを無効化 (クリエイティブを除く)
- ロビーでのアイテムのドロップを無効化 (クリエイティブを除く)
- ロビーでの食事を無効化 (クリエイティブを除く)

### EnderChestsPerWorld
ワールドごとにプレイヤーのエンダーチェストを作る機能。実質的にはエンダーチェストの内容が保持されない問題への対処をしている。

### PrivateCraftingForBrewingAndSmelting
プライベートかまど・醸造台を実装している。プライヤーが置いたブロックはプライベートかまど・醸造台にならない。

### CombatAdjustment
戦闘における仕様調整を担う。
#### 弓矢
- ダメージ 1/2
- パンチ(エンチャント)の効果 1/2
