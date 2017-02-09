bpushはLAMP環境で動作するように設計されています。

# 依存システム

 - PHP 5.5 or later
 - MySQL 5.5 or later
 - Redis 3.0 or later
 - NodeJS 5.0 or later
 - supervisord 3.0 or later
 - Java 1.8 or later
 - SSL証明書と専用のドメイン

## PHP拡張 

下記のPHP拡張をインストールしてください。

 - pdo
 - pdo_mysql
 - mysqlnd
 - json
 - mbstring
 - dom
 - curl
 - intl
 - opcache (推奨)


##  Apacheの設定

 - mod_rewrite を有効にしてください
 - AllowOverrideにAllを設定してください

## 外部アカウントのセットアップ

# Google

## Google Api Console

Google API Consoleよりプッシュ通知APIを使うための準備を行う。
URL: https://console.developers.google.com/

1. プロジェクトがない場合は作成する
2. ダッシュボードよりGoogle Cloud Messaging を有効にする
3. 認証情報ページより「認証情報を作成」を選択し、APIキーを作成する

取得する情報

 - APIキー

## Google Cloud Platform

Google Cloud Platform にてProject Number(IDの横に記載された番号)を確認する
URL: https://console.cloud.google.com/

取得する情報

 - プロジェクト番号

# AWS

1. AWSの登録を行いアカウントを入手する
2. AWS IAM(Identity and Access Management) よりAWS SNSを利用できるユーザーを作成する
3. AWS SNS(Simple Notification Service) にてGCMのApplicationを作成し、ARNを発行する。(Google API Keyが必要)

取得する情報

 - アクセスキー
 - アクセスキーシークレット
 - ARN

# ライブラリのインストールと初期設定

1. 初期化スクリプトの実行

```
./bin/init.sh
```

2. config.php の作成

config.php.template から config.php を作成し、設定項目を入力します。

3. 初期ファイルの生成

``
php scripts/generate_init_files.php
```

4. cronの設定

下記２ファイルをcronに指定してください。

 - scripts/send.php (1分毎)
 - scripts/watch_rss_feed.php (5分毎)

5. バックグラウンドプロセスの設定

`scripts/notifier.php` はphpのクライアントモードで起動するバックグラウンドプロセスです。
supervisord などのソフトウェアを使いデーモンとして起動する設定を行ってください。

