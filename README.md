# WebSVN - Online subversion repository browser

This project is a fork of [websvnphp/websvn](https://github.com/websvnphp/websvn)

- [WebSVN - Online subversion repository browser](#WebSVN---Online-subversion-repository-browser)
  - [Description](#Description)
  - [Features](#Features)
  - [Configuration](#Configuration)
  - [Screenshots](#Screenshots)

## Description

WebSVN offers a view onto your Subversion repositories that's been designed to reflect the Subversion methodology.

## Features

This fork/branch has been updated with markdown support via [parsedown](https://github.com/erusev/parsedown) and [parsedown-toc](https://github.com/BenjaminHoegh/parsedown-toc), and it will display the README file for any directory that has one, ala GitHub/VisualSVN, when viewing that directory.  A GitHub-ish theme has also been added, although not quite complete.

Base features:

- View the log of any file or directory and see a list of all the files changed, added or deleted in any given revision.
- View the differences between two versions of a file so as to see exactly what was changed in a particular revision.

## Configuration

WebSVN configuration is done through the config file include/config.php.

The markdown support with README display looks and works best with the WebSVN "flat" view.  You may want to change from the default "tree" view by adding/uncommenting the following in config.php:

    $config->useFlatView();

To use the provided GitHub-ish theme, add the following line to config.php:

    $config->addTemplatePath($locwebsvnreal.'/templates/github/');

## Screenshots

![Screenshot1](trunk-page-1.png "Trunk Page")

![Screenshot2](trunk-page-2.png "Trunk Page - Same directory view with README.md")

![Screenshot3](screenshot.png "Same directory view with README.md")
