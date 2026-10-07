# Google Indexing API Extension for MediaWiki

An official MediaWiki extension developed by Mippedia Community to automate real-time instant indexing via Google Indexing API whenever pages are created, edited, or deleted.

## 🚀 Features
* Instant Indexing: Automatically notifies Google when articles are created or edited (`URL_UPDATED`).
* Auto De-indexing: Notifies Google when articles are deleted (`URL_DELETED`).
* Asynchronous Execution: Uses MediaWiki's `DeferredUpdates` to ensure non-blocking performance.
* Lightweight & Silent: Pure PHP JWT authentication without heavy external dependencies.

## 🛠️ Installation

### Option 1: Git Clone
Navigate to your MediaWiki `extensions/` directory and run:

```cd extensions/```
```git clone https://github.com/MippediaCommunity/GoogleIndexing.git```

or

```https://github.com/MippediaCommunity/GoogleIndexing.git```

### Option 2: Manual Installation

Create a folder extensions/GoogleIndexing/.
Copy extension.json and includes/Hooks.php into the directory.

## ⚙️ Configuration
Add the following to your LocalSettings.php:

```wfLoadExtension( 'GoogleIndexing' );```
```$wgGoogleIndexingJsonPath = '/path/to/your/google-key.json';```

## 📄 License
Developed by Mippedia Community. Released under the GPL-2.0-or-later license.
