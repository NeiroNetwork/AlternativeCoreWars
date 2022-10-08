# AlternativeCoreWars
代替 - 核 - 戦争

## イベント一覧
| イベント             | 説明                |
|------------------|-------------------|
| GameCleanupEvent | ゲームの後片付けを行うイベント   |
| GameSettleEvent  | ゲームが決着した時のイベント    |
| GameStartEvent   | ゲームが始まった時のイベント    |
| NexusDamageEvent | ネクサスが破壊されたときのイベント |
| PhaseStartEvent  | フェーズが始まった時のイベント   |

## 仕様
### ServerSpecificationNormalizer
#### サーバーの設定ファイル編集
##### `server.properties`
- サーバーデフォルトゲームモードをアドベンチャーに設定
- PvPを有効化
- ワールドの生成タイプを`VOID`に変更 (新規に作成されたワールドしか影響を受けないが、なんとなく変更しておく)
- クエリー無効化
- オートセーブ無効化
##### `pocketmine.yml` (こっちは保存されない)
- クエリーの結果にプラグイン一覧を含めないように
- メインスレッドのメモリ制限を4096MBに変更
- 非同期ワーカーのメモリ制限を256MBに強制
- パケットの圧縮率の設定を2に変更
- パケットの非同期圧縮を有効化
- プレイヤーデータの保存を無効化
- クラッシュレポートの送信を無効化
- 匿名の使用状況(メトリクス)収集を無効化
#### コマンドの変更
- コマンド `defaultgamemode`, `save-all`, `save-on`, `save-off`, `setworldspawn`, `spawnpoint` を無効化
- コマンド `op`, `dumpmemory` の実行権限をコンソールのみに変更
#### 既存のブロック、アイテムの置き換え
##### ブロック
| ブロック    | 変更内容                                                                            |
|---------|---------------------------------------------------------------------------------|
| 草       | 種のドロップ率変更 (6.25% → 0.662%)                                                      |
| 葉っぱ     | リンゴのドロップ率変更 (0.5% → 5%)<br/>苗木のドロップ率変更 (5% → 2.5%)                              |
| 小麦      | 完全に成長した時の種のドロップ数を変更 (0\~4 → 0\~1)                                               |
| ネザーウォート | ドロップ数変更 (2\~4 → 0\~2, 未成長: 1 → 0\~1)<br/>発酵したクモの目をドロップ (6.25%)<br/>火薬をドロップ (4%) |
#### レシピの変更
- 弓、矢をクラフティングレシピから削除
#### イベントリスナー
- ワールド読み込み時にワールドの時間を止める

### NoDeathScreenSystem
- スペクテイターの自殺と奈落ダメージの無効化
- プレイヤーが死んだ場合 `PlayerDeathEvent` の代わりに `PlayerDeathWihtoutDeathScreenEvent` を呼び出す
- `PlayerRespawnEvent` は基本的には呼び出されない
- 死亡したメッセージをプレイヤーがいるワールドのみに送信する

### SoulboundItemMonitor
- インベントリトランザクションを監視し、移動先のインベントリが許可されていない場合に、アイテムを虚無へ送るアクションを追加する (`SlotChangeAction` が対象)
- プレイヤーがアイテムをドロップさせた場合は、アイテムの数を0にする
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
プライベート醸造台を使うのに燃料(ブレイズパウダー)は要らないようになっている。

### CombatAdjustment
戦闘における仕様調整を担う。
#### 弓矢
- パンチ(エンチャント)の効果 1/2

### GameArenaProtector
ゲームアリーナ内での保護範囲の処理などを行う
- ブロックに埋まるなどのグリッチを対策
- 保護範囲内でのブロックの破壊・設置を無効化
- 保護範囲内でのバケツの使用を無効化
- 保護範囲内でのツール・骨粉・絵画の使用を無効化
- 保護範囲の中に構造物を成長させようとするのを防ぐ
- 保護範囲内のブロックが爆発により壊れないようにする

### ChatRouter
チームチャットの処理を行っている(が、それ以外にも色々使えるようになっているはず)
#### ルーティングのルール
- 優先度LOWEST: 様々な状況から、チャットのルーティング先を判定、チャットメッセージの変更をする
- 優先度NORMAL: チャットの送信先を実際に設定(イベントを変更)する
- 優先度MONITOR: フラグの削除を行う
#### チームチャットの仕様
- チームに所属しているプレイヤーのチャットは、デフォルトでチームチャットになる
- `!こんにちは` のようにすることで全体チャットができる
- ロビーにいるプレイヤーのチャットは全体チャット
- チームチャットはグレー、全体チャットはデフォルト色で表示
