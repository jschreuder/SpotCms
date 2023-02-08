angular.module("SpotCms", ["ngMaterial"]);

angular.module("SpotCms").controller("pageHeader", ["$mdSidenav", function ($mdSidenav) {
    this.sideNavToggle = function () {
        $mdSidenav('main-menu').toggle();
    };
}]);
