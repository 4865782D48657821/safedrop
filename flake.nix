{
  description = "Safedrop Laravel MVP development environment";

  inputs.nixpkgs.url = "github:NixOS/nixpkgs/nixos-unstable";

  outputs = { nixpkgs, ... }:
    let
      systems = [
        "aarch64-darwin"
        "x86_64-darwin"
        "aarch64-linux"
        "x86_64-linux"
      ];

      forAllSystems = function:
        builtins.listToAttrs (map (system: {
          name = system;
          value = function system;
        }) systems);
    in
    {
      devShells = forAllSystems (system:
        let
          pkgs = import nixpkgs { inherit system; };
          php = pkgs.php83.withExtensions ({ enabled, all }:
            pkgs.lib.unique (enabled ++ (with all; [
              curl
              dom
              fileinfo
              mbstring
              openssl
              pdo
              pdo_sqlite
              sqlite3
              tokenizer
            ])));
        in
        {
          default = pkgs.mkShellNoCC {
            packages = [
              php
              pkgs.php83Packages.composer
              pkgs.sqlite
            ];

            APP_ENV = "local";
            DB_CONNECTION = "sqlite";
            DB_DATABASE = "database/database.sqlite";
          };
        });

      formatter = forAllSystems (system:
        let
          pkgs = import nixpkgs { inherit system; };
        in
        pkgs.nixpkgs-fmt);
    };
}
