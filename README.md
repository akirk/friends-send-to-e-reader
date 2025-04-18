# Friends Send to E-Reader

Send new articles directly to your e-reader via e-mail or download the ePub.

**Contributors:** akirk
**Requires at least:** 5.0
**Tested up to:** 6.3
**Requires PHP:** 5.2.4
**License:** [GPLv2 or later](http://www.gnu.org/licenses/gpl-2.0.html)
**Stable tag:** trunk

## Description

See the post [Subscribing to RSS Feeds on your E-Reader using your own WordPress blog](https://wpfriends.at/2021/09/20/subscribing-to-rss-feeds-on-your-e-reader/) for more details on how it works.

This plugin is meant to be used with the [Friends plugin](https://github.com/akirk/friends/).

## Changelog

### 0.8.4
- Allow creating books via bulk edit ([#13])
- Fixed a bug where a non-existant image could cause the rest of the document to be a link
- Enable the URL GET parameter on any page
- One more fix for empty titles in posts

### 0.8.3
- Try harder to ensure the title is not empty ([#12])

### 0.8.2
- Ensure the title is not empty ([#11])
- Improve the Reading Summary function ([#9])

### 0.8.1
- Add a Download URL previewer ([#7])
- Add the ability to mark an article as new ([#6])

### 0.8.0
- Fix choking on invalid SVGs
- Enable unsent posts for any author
- Add the ability to download ePub through special URLs ([#5])

### 0.7
- Fix multi-item dialog not popping up.

### 0.6
- Remove MOBI support since Amazon now accepts EPubs by mail.
- Introduce Reading Summaries: You can create a new draft posts from your sent articles so that you can easily post about them.
- Remember which posts were already sent, enabling a "Send x new posts to your e-reader" button in the header.

### 0.5
- Remember which posts were sent and allow sending just the new ones. [WIP display works, actual sending not yet]
- Automatically send new posts every week. [WIP setting screen is there, saving setting and cron not yet]
- Allow auto-creating of "reading summary" draft posts with link plus excerpt and room for your own comments.
- New-style setting screen with separate screen for reading summaries.

### 0.4
- Update for Friends 2.0

### 0.3
- Allow downloading the ePub.
- Theoretically add support for Tolino. Not functional because Thalia doesn't want to provide OAuth2 credentials.

[#12]: https://github.com/akirk/friends-send-to-e-reader/pull/12
[#11]: https://github.com/akirk/friends-send-to-e-reader/pull/11
[#9]: https://github.com/akirk/friends-send-to-e-reader/pull/9
[#7]: https://github.com/akirk/friends-send-to-e-reader/pull/7
[#6]: https://github.com/akirk/friends-send-to-e-reader/pull/6
[#5]: https://github.com/akirk/friends-send-to-e-reader/pull/5
