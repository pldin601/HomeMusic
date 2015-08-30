// Generated by CoffeeScript 1.9.3
(function() {
  var MusicLoud;

  MusicLoud = angular.module("MusicLoud");

  MusicLoud.config([
    "$indexedDBProvider", function($indexedDBProvider) {
      return $indexedDBProvider.connection("MusicLoud").upgradeDatabase(1, function(event, db, tx) {
        var trackStorage;
        trackStorage = db.createObjectStore('tracks', {
          keyPath: 'id'
        });
        trackStorage.createIndex('track_title_idx', 'track_title', {
          unique: false
        });
        trackStorage.createIndex('track_artist_idx', 'track_artist', {
          unique: false
        });
        trackStorage.createIndex('track_album_idx', 'track_album', {
          unique: false
        });
        trackStorage.createIndex('track_genre_idx', 'track_genre', {
          unique: false
        });
        trackStorage.createIndex('track_year_idx', 'track_year', {
          unique: false
        });
        trackStorage.createIndex('track_rating_idx', 'track_rating', {
          unique: false
        });
        trackStorage.createIndex('is_favourite_idx', 'is_favourite', {
          unique: false
        });
        return trackStorage.createIndex('album_artist_idx', 'album_artist', {
          unique: false
        });
      }).upgradeDatabase(2, function(event, db, tx) {
        var infoStorage;
        return infoStorage = db.createObjectStore('misc', {
          keyPath: 'key'
        });
      });
    }
  ]);

  MusicLoud.run([
    "$indexedDB", "DBSyncService", function($indexedDB, DBSyncService) {
      return $indexedDB.openStore('misc', function(store) {
        return store.find('sync_token').then(function(data) {
          return data.value;
        })["catch"](function() {
          return 0;
        }).then(function(token) {
          return store.upsert({
            key: 'sync_token',
            value: token
          });
        });
      });
    }
  ]);

  MusicLoud.service("DBSyncService", [
    "$http", function($http) {
      return {
        loadAll: function() {
          return $http.get("/api/resources/tracks/all");
        }
      };
    }
  ]);

}).call(this);

//# sourceMappingURL=localstore.js.map
