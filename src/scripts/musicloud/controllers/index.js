/*
 * Copyright (c) 2017 Roman Lakhtadyr
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */
import ArtistViewController from './ArtistViewController'
import GenreViewController from './GenreViewController'
import AlbumViewController from './AlbumViewController'
import TracksViewController from './TracksViewController'
import AllArtistsViewController from './AllArtistsViewController'
import AllAlbumsViewController from './AllAlbumsViewController'
import AllCompilationsViewController from './AllCompilationsViewController'
import AllGenresViewController from './AllGenresViewController'
import PlaylistsController from './PlaylistsController'
import PlaylistController from './PlaylistController'
import SearchController from './SearchController'
import MetadataController from './MetadataController'
import UploadController from './UploadController'

const controllers = {
  ArtistViewController,
  GenreViewController,
  AlbumViewController,
  TracksViewController,
  AllArtistsViewController,
  AllAlbumsViewController,
  AllCompilationsViewController,
  AllGenresViewController,
  PlaylistsController,
  PlaylistController,
  SearchController,
  MetadataController,
  UploadController,
}

export default (app) =>
  Object.keys(controllers).forEach((name) => app.controller(name, controllers[name]))
