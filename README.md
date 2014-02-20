op5 Monitor ([Download v0.1.0](https://raw.github.com/fibbs/alfred-op5-workflow/master/op5Monitor-workflow.alfredworkflow))
==============================

Alfred 2 workflow for the commercial Nagios-based network monitoring
solution op5 Monitor

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
1. Click the download buttons below
2. Double-click to import into Alfred 2
3. Review the workflow to add custom Hotkeys

## Updating
Run the [Alleyoop Workflow](http://www.alfredforum.com/topic/1582-alleyoop-update-alfred-workflows/) using the keyword `oop`. If you're not comfortable with Alleyoop, **star & watch this repo** to keep up to date on new versions and additional workflows.

## About
Alfred App Workflow for op5 Monitor.

![alt text][op5-workflow]

### Query module
The query module contains the main functions of this workflow and can be
called by the shortcut command "monq" by default. 

#### Querying with shortcuts
The query module can
query op5 Monitor for several object types by prefixing the query with
one of the following options:

- *h:* for hosts,
- *s:* for services,
- *g:* for host groups,
- *G:* for service groups and
- *f:* or *+* for saved filters

additionally, you can use

- *'* to pass lsfilter filters (those that you can build in the
  graphical filter builder in op5 Monitor's web UI, for example by copy
and paste.

If your query doesn't contain one of the above prefixes, the "default
mode" is used. This default mode is configurable. If you didn't set it
up, it will be "hosts".

Therefore, just hitting <cmd>-<space> an "monq" will give you a list of
all your hosts in op5 Monitor with their corresponding states.

You can add search queries to each of the above mentioned prefix. Text
entered after the prefix is handled as search query. You can even use
regular expressions.

- `monq h:server-00[123]`

This example will list hosts "server-001", "server-002", "server-003" if
existing.

Additionally, you can add a "!" before the search query to negate the
search:

- `monq g:!web`

This example will show all host groups that do not contain the word
"web" in their name or aliases.

Last but not least, you can use the "#" suffix to any of the queries to
ONLY list objects with PROBLEMS:

- `monq G:#`

This example will list all service groups (not filtering by name) that
contain any non-OK services.

#### Querying with lsfilters
When using the *'* prefix to directly enter lsfilter filters, none of
the above mentioned options can be used. But, in any case, you can
always change the filter syntax directly in Alfred's prompt.

#### Object navigation using the tab key
Most of the displayed objects (in fact, all except "services" as they
are the last piece in the row) support two options: tab-completion to
jump into the next lower-level elements and enter action for
object-related actions.

Tab-completion works as follows:
- hosts tab completion leads to a service overview for this specific
  host
- host group tab completion leads to an overview of all members of the
  host group including their corresponding states
- service group tab completion leads to an overview of all services in
  this specific service group, including their states
- saved filters tab completion leads to the actual result of the
  corresponding filter

#### Select-action for objects
This feature is not yet implemented!

## Configuration module
By calling the workflow with the `monc` command you will be presented
with the options to configure following necessary values:

- hostname (op5 Monitor FQDN or IP address)
- username for op5 Monitor login
- password
- default mode (default is "hosts")



## Commands
- `monq {query}`
- `monc`
- `mona`

[op5-workflow]: ./screenshots/op5-workflow-001.png "Sample op5 Monitor query"
