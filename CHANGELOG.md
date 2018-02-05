# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## [3.0.0.0] - 2016-07-10 10:02:00
### Modified
- Refactored version

## [3.0.0.1] - 2016-07-12 10:02:00
### Modified
- Code style formatting according to psr standards
### Added
- Collection files

## [3.0.0.2] - 2016-07-16 10:02:00
### Added
- Install scripts
- Getting sitemap.xml at the install 

## [3.0.0.2] - 2016-07-16 10:02:00
### Added
- Install scripts
- Getting sitemap.xml at the install 

## [3.0.0.3] - 2016-07-20 10:02:00
### Modified
- Code style formatting according to psr standards

## [3.0.0.4] - 2016-07-21 10:02:00
### Modified
- Move config values to core_config_data table

## [3.0.0.5] - 2016-07-22 10:02:00
### Modified
- Server endpoint

## [3.0.0.6] - 2016-07-26 10:02:00
### Modified
- Reduce max products on xml page at fetch to 1000
### Added
- GET input validation
### Removed
- Magento api usage of changing SERP mode

## [3.0.0.7] - 2016-07-31 10:02:00
### Modified
- Use Renderer as Helper Class
- Use Magento Collections Api to retrieve data
- Use Curl lib instead of Varie_Http_Client
### Added
- Is_loggedin in user info for frontend injection
- Usage of Varien_Profiler 
### Removed
- Drop old isp_config if exists
- validateInput method
- Checksum api

## [3.0.0.8] - 2016-08-02 10:02:00
### Modified
- Refactor getting product attributes

## [3.0.0.9] - 2016-09-21 15:29:00
### Fixed
- Urlencoding of serp template
### Added
- sku in the list of product attributes
- default values for offset and count

## [3.0.0.10] - 2016-09-22 16:42:00
### Fixed
- Fix multistores json by adding url and lang by scope and entity id (stores2)
- Fix recording product updates for already updated item
### Added
- Xml header in searches controller
- Send magento updates using collection api
- Flat categories catalog support
- Cache serp template for 1 minute

## [3.0.0.12] - 2016-10-27 17:48:00
### Fixed
- Fix get orders per product
### Added
- Filedocs, Classdocs, Methoddocs
- Code formatting according to psr standards
- isp_sellable custom attribute support
- Alternative way to get product url if regular way does not work
- Attributes cache
- Flat catalog usage flags for vers request
- Json headers for getstores
### Removed
- Raw sql searches tables access