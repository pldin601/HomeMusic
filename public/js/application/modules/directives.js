/**
 * Created by Roman on 28.07.2015.
 */

(function () {

    var MusicLoud = angular.module("MusicLoud");

    MusicLoud.directive("peakData", ["$rootScope", "TrackService", "$window", function ($rootScope, TrackService, $window) {
        return {
            restrict: "A",
            link: function (scope, element, attrs) {
                var peaksData = [],
                    w = angular.element($window),
                    canvas = element[0],
                    ctx = canvas.getContext("2d"),

                    clearPeaks = function () {
                        peaksData = [];
                        drawCanvas();
                        scope.loading = false;
                    },
                    loadPeaks = function (data) {
                        if (angular.isArray(data)) {
                            peaksData = data;
                            drawCanvas();
                        }
                        scope.loading = false;
                    },
                    drawCanvas = function () {
                        var gradientBase, peak, pos, rate, leftRange, rightRange;

                        canvas.width = element.width();
                        canvas.height = element.height();

                        gradientBase = canvas.height * .75;
                        rate = (peaksData.length / canvas.width) * 3;

                        ctx.fillStyle = "#223344";
                        ctx.globalCompositeOperation = "xor";
                        ctx.fillRect(0, 0, canvas.width, canvas.height);
                        ctx.fill();
                        ctx.beginPath();
                        for (var n = 0; n <= canvas.width; n += 1) {
                            if (n % 3 == 2)
                                continue;

                            pos = parseInt(peaksData.length / canvas.width * (n - n % 3));
                            leftRange = Math.max(0, pos - (rate / 2));
                            rightRange = Math.min(peaksData.length - 1, pos + (rate / 2));
                            peak = 1 / 127 * peaksData.slice(leftRange, rightRange).max();

                            ctx.moveTo(n + .5, parseInt(gradientBase - (gradientBase * peak)) - 1);
                            ctx.lineTo(n + .5, parseInt(gradientBase + ((canvas.height - gradientBase) * peak)) + 1);
                        }
                        ctx.strokeStyle = "#000000";
                        ctx.stroke();
                    };

                var watcher = $rootScope.$watch("player.playlist.track", function (changed) {
                    scope.loading = true;
                    if (!changed) {
                        clearPeaks();
                    } else {
                        TrackService.getPeaks(changed.id).then(loadPeaks, clearPeaks);
                    }
                });

                scope.$on("$destroy", function () {
                    watcher();
                    w.unbind("resize", drawCanvas);
                });

                scope.loading = false;

                w.bind("resize", drawCanvas);

            }
        };
    }]);

    MusicLoud.directive("multiselectList", [function () {
        return {
            scope: {
                multiselectList: "@",
                multiselectDestination: "="
            },
            link: function (scope, elem, attrs) {

                var countSelected = function () {

                        var all = elem.find("." + scope.multiselectList + "[multiselect-item]");

                        array_copy(all.map(function () {
                            var el = angular.element(this);
                            return el.scope()[el.attr("multiselect-item")]
                        }).toArray(), scope.multiselectDestination);

                    },
                    lastSelected = null;

                elem.on("selectstart", function (event) {
                    event.stopPropagation();
                    event.preventDefault();
                });

                elem.on("click touch", function (event) {
                    select(event, false);
                });

                function select(event, force) {
                    scope.$applyAsync(function () {
                        var all = elem.find("[multiselect-item]"),
                            selected = angular.element(event.target).parents("[multiselect-item]"),
                            left, right;

                        if (!(event.ctrlKey || event.metaKey || force)) {
                            all.toggleClass(scope.multiselectList, false);
                        }
                        if (selected.length == 1) {
                            if (event.shiftKey && lastSelected) {
                                left = all.index(lastSelected);
                                right = all.index(selected);

                                ((left < right) ? all.slice(left, right) : all.slice(right, left))
                                    .add(selected)
                                    .add(lastSelected)
                                    .toggleClass(scope.multiselectList, true)
                            } else {
                                selected.toggleClass(scope.multiselectList);
                                lastSelected = selected;
                            }
                        } else {
                            lastSelected = null;
                        }
                        countSelected();
                    });
                }

            }
        }
    }]);

    MusicLoud.directive("progressBar", [function () {
        return {
            scope: {
                progressBar: "="
            },
            restrict: "A",
            link: function (scope, element, attributes) {
                scope.$watch("progressBar", function (value) {
                    element.css("width", "" + value + "%");
                });
            }
        }
    }]);

    MusicLoud.directive("clickOutside", ["$document", "$parse", "$rootScope", function ($document, $parse, $rootScope) {
        return {
            restrict: "A",
            compile: function ($element, attributes) {

                var fn = $parse(attributes["clickOutside"], null, true);

                return function (scope, element) {

                    var callback = function () {
                            fn(scope, {$event: event})
                        },
                        bindingFunction = function (event) {

                            if (element.find(event.target).length == 0) {
                                if ($rootScope.$$phase) {
                                    scope.$evalAsync(callback);
                                } else {
                                    scope.$apply(callback);
                                }
                            }

                        };

                    $document.bind("click", bindingFunction);

                    scope.$on("$destroy", function () {
                        $document.unbind("click", bindingFunction);
                    });

                };


            }
        }
    }]);

    MusicLoud.directive("mlEnter", ["$document", "$parse", "$rootScope",
        function ($document, $parse, $rootScope) {
            return {
                restrict: "A",
                compile: function ($element, attributes) {

                    var fn = $parse(attributes["mlEnter"], null, true);

                    return function (scope, element) {

                        var callback = function () {
                                fn(scope, {$event: event})
                            },
                            bindingFunction = function (event) {

                                if (event.which == 13) {

                                    if ($rootScope.$$phase) {
                                        scope.$evalAsync(callback);
                                    } else {
                                        scope.$apply(callback);
                                    }

                                }

                            };

                        $document.bind("keypress", bindingFunction);

                        scope.$on("$destroy", function () {
                            $document.unbind("keypress", bindingFunction);
                        });

                    }
                }
            }
        }
    ]);


})();
