op5 Monitor ([Download v0.2.0](https://github.com/fibbs/alfred-op5-monitor-workflow/raw/master/op5Monitor-workflow.alfredworkflow))
==============================

Alfred 2 workflow for the commercial Nagios-based network monitoring
solution *op5 Monitor*

## Requirements
1. Alfred App v. 2
1. Alfred Powerpack
1. op5 Monitor v. 6.3 or higher (currently not released yet)

**BIG FAT WARNING**
The required version of op5 Monitor that is needed for this Alfred
workflow to work is not yet released. You may contact me if you are
interested in helping me testing this workflow!
op5 Monitor is planned to be released within the second quarter of 2014.

## Installing
1. Click the download buttons on the top of this page
2. Double-click to import into Alfred 2
3. Review the workflow to add custom Hotkeys

## Updating
Unfortunately the [Alleyoop Workflow](http://www.alfredforum.com/topic/1582-alleyoop-update-alfred-workflows/) that offered a great package manager for Alfred workflows seems to be discontinued. This workflow still follows the requirements to work with Alleyoop, but it will probably move over to [Packal](http://www.packal.org) soon. You can find more information about Packal [here](http://www.alfredforum.com/topic/3730-new-workflow-and-theme-repository-packal/).

## About
Alfred App Workflow for op5 Monitor.

![alt text][op5-workflow-img001]
![alt text][op5-workflow-img002]

### Query module
The query module contains the main functions of this workflow and can be
called from the Alfred popup by typing the shortcut command "mon" by default. 

#### Querying Shortcuts
The query module can query op5 Monitor for several object types by prefixing the query with
one of the following options:

- `h:` for hosts,
- `s:` for services,
- `g:` for host groups,
- `G:` for service groups and
- `f:` or `+` for saved filters

![alt text][op5-workflow-img003]
![alt text][op5-workflow-img004]

You can add search queries to each of the above mentioned query prefixes. Text
entered after the prefix is handled as search query. You can even use
regular expressions.

- `monq h:host-00[123]`

This example will list hosts "host-001", "host-002", "host-003" if
existing.

![alt text][op5-workflow-img005]

Additionally, you can add a `!` before the search query to negate the
search query:

- `monq g:!host`

This example will show all host groups that do not contain the word
"host" in their names and aliases.

![alt text][op5-workflow-img006]

Last but not least, you can use the `#` suffix to any of the queries to
ONLY list objects with PROBLEMS:

- `monq g:#`

This example will list all host groups (not filtering by name) that
contain any non-OK services.

![alt text][op5-workflow-img007]

#### Querying with Listview-filters
By using the `'` prefix you can directly enter a listview filter. Listview filters were introduced in the user interface of op5 Monitor in version 6.1. They are a great way to customize the view a list view provides. This Alfred workflow makes a lot of use of these listview filters. When entering a listview filter directly via the `'` prefix, none of the above mentioned additional filter options auch as `#` and `!` can be used. But, in any case, you can
always change the filter syntax directly in Alfred's prompt.

![alt text][op5-workflow-img008]

#### Object navigation using the tab key
Most of the displayed objects (in fact, all except "services" as they are the most low-level objects in the chain) support two options: tab-completion to jump into the next lower-level element and enter action for object-related actions.

Tab-completion works as follows:
- hosts tab completion leads to a service overview for this specific host
- host group tab completion leads to an overview of all members of the host group including their corresponding states
- service group tab completion leads to an overview of all services in this specific service group, including their states
- saved filters tab completion leads to the actual result of the corresponding filter

#### Select-action for objects
This feature is not yet implemented!

## Configuration module
By calling the workflow with the `monc` command you will be presented
with the options to configure following necessary values:

- hostname (op5 Monitor FQDN or IP address)
- username for op5 Monitor login
- password

![alt text][op5-workflow-img009]

## Commands
- `monq {query}`
- `monc`
- `mona`

## Donate
I have created this workflow mainly for myself. But if it is as useful
for you as it is for me, and you feel the need to drop me some cents or
euros for it, I will not stop you from doing so.

[![Donate](https://www.paypalobjects.com/en_US/i/btn/btn_donate_LG.gif)](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=32WRFW8GBHLWJ)


[op5-workflow-img001]: ./screenshots/op5-workflow-001.png "op5 Monitor Workflow query modes overview"
[op5-workflow-img002]: ./screenshots/op5-workflow-002.png "Sample op5 Monitor workflow host query"
[op5-workflow-img003]: ./screenshots/op5-workflow-003.png "Listing host group objects using prefix"
[op5-workflow-img004]: ./screenshots/op5-workflow-004.png "Listing saved filters from op5 Monitor"
[op5-workflow-img005]: ./screenshots/op5-workflow-005.png "Filtering using regular expression"
[op5-workflow-img006]: ./screenshots/op5-workflow-006.png "Negating a filter"
[op5-workflow-img007]: ./screenshots/op5-workflow-007.png "only show objects that have problems"
[op5-workflow-img008]: ./screenshots/op5-workflow-008.png "Enter a filter directly"
[op5-workflow-img009]: ./screenshots/op5-workflow-009.png "Configuration"
