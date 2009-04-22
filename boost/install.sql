-- $Id: install.sql,v 1.12 2008/08/16 20:19:12 blindman1344 Exp $

CREATE TABLE ads_zones (
id INT NOT NULL,
key_id INT DEFAULT '0' NOT NULL,
title VARCHAR(100) NOT NULL,
description TEXT NULL,
ad_type SMALLINT NOT NULL,
max_num_ads SMALLINT NOT NULL,
active SMALLINT NOT NULL,
PRIMARY KEY (id)
);

CREATE TABLE ads_zone_pins (
zone_id INT DEFAULT '0' NOT NULL,
key_id INT DEFAULT '0' NOT NULL
);

CREATE TABLE ads_advertisers (
user_id INT DEFAULT '0' NOT NULL,
created INT NOT NULL
);

CREATE UNIQUE INDEX userid_idx on ads_advertisers(user_id);

CREATE TABLE ads_campaigns (
id INT NOT NULL,
advertiser_id INT DEFAULT '0' NOT NULL,
name VARCHAR(100) NOT NULL,
priority SMALLINT NOT NULL,
created INT NOT NULL,
PRIMARY KEY (id)
);

CREATE TABLE ads_campaign_pins (
campaign_id INT DEFAULT '0' NOT NULL,
zone_id INT DEFAULT '0' NOT NULL
);

CREATE TABLE ads (
id INT NOT NULL,
campaign_id INT DEFAULT '0' NOT NULL,
title VARCHAR(100) NOT NULL,
ad_type SMALLINT NOT NULL,
filename VARCHAR(255) NULL,
width INT NULL,
height INT NULL,
ad_text TEXT NULL,
url VARCHAR(255) NULL,
active SMALLINT NOT NULL,
approved SMALLINT NOT NULL,
created INT NOT NULL,
PRIMARY KEY (id)
);

CREATE TABLE ads_stats (
id INT NOT NULL,
views INT DEFAULT '0' NOT NULL,
hits INT DEFAULT '0' NOT NULL,
PRIMARY KEY (id)
);
