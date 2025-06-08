#!/usr/bin/env sh

program_name=$(basename -- "$0")
help_msg="\
Usage:  $program_name [OPTIONS] [ARGUMENTS]
        $program_name [-q|--quiet] File [Group]

Arguments:
    Group           Group name to search
    File            File with .csv extension to parse from

Options:
    -h, --help      Print help
    -V, --version   Print version
    -q, --quiet,    Disable all output to stdout (except -h and -V)
        --silent\
"

abort() { # message, exit code (default 1)
    __abort_output=2 # stderr
    __abort_exit_code=1

    if [ "$2" = '0' ]; then
        __abort_output=1 # stdout
        __abort_exit_code=0
    fi

    printf '%s\n' "$1" >& $__abort_output
    exit "$__abort_exit_code"
}

quiet=false

while [ $# -ne 0 ]; do
    case "$1" in
        '-h' | '--help')
            abort "$help_msg" 0 ;;
        '-q' | '--quiet' | '--silent')
            quiet=true ;;
        '-V' | '--version')
            abort "$program_name, SemVer 1.0.0" 0 ;;
        -*)
            abort "Unknown argument: '$1'!" ;;
        *.csv)
            test -n "$file" && abort "Redundant option: '$1'!";
            file=$1
            ;;
        *)
            test -n "$group" && abort "Redundant option: '$1'!"
            group=$1
            ;;
    esac

    shift 1
done


select_menu() { # var_name_to_write_to, [selection options]
    __select_menu_ptr=$1
    shift 1

    echo "Select a $__select_menu_ptr:"

    test $# -eq 0 && abort 'Nothing to select.'
    if [ $# -eq 1 ]; then
        eval "$__select_menu_ptr=$1"
        return 0
    fi

    select choice in exit "$@"; do
        case "$choice" in
            '') ;; # invalid choices
            'exit') abort 'Exiting.' 0 ;;
            *) eval "$__select_menu_ptr=$choice"; return 0 ;;
        esac
    done
}

if [ -z "$file" ]; then
    $quiet && abort 'File not specified!'
    select_menu file $(echo TimeTable_*.csv | tr ' ' '\n' | sort -nr -t '_' -k4 -k3 -k2)
fi

data=$(cat -- "$file" 2>/dev/null) || abort "Can't read file '$file'!"
test -z "$data" && abort "File '$file' is empty!"

data=$(printf '%s' "$data" | sed 's/\r/\n/g' | iconv -f CP1251 -t UTF8) ||
abort "Can't convert file '$file' to UTF-8!"

groups=$(printf '%s' "$data" | sed '1d; / - /!d; s/^"//; s/ - .*//' | sort -u)

if [ -z "$groups" ]; then
    # $quiet || echo 'File has no groups, converting as is.' >&2
    unset group
elif [ -z "$group" ]; then
    echo 'Multiple groups are present but no group is specified!' >&2
    $quiet && exit 1
    select_menu group $groups
elif ! printf '%s' "$groups" | grep -q "^$group$" ; then
    echo 'Group not found!' >&2
    $quiet && exit 1
    select_menu group $groups
fi


exec 3>&1
$quiet && exec 3>/dev/null

printf '%s' "$data" | awk -v FS='","' -v group="$group" '
    BEGIN { print "Subject,Start Date,Start Time,End Date,End Time,Description" }

    function date(d) { # d.m.y to m/d/y
        split(d, p, ".")
        return sprintf("%s/%s/%s", p[2], p[1], p[3])
    }

    function time(t) { # h:m:s to h:m AM/PM (wrong at 0 & 24 hours yet simple)
        split(t, p, ":")
        return sprintf("%s:%s %s", p[1]>12 ? p[1]-12 : p[1], p[2], p[1]>12 ? "PM":"AM")
    }

    NR>1 && $1 ~ "^\"" group {
        gsub(group " - |\"$", "", $1)
        printf("%s; №%d\",%s,%s,%s,%s,\"%s\"\n",
            $1, ++num[$2], date($2), time($3), date($4), time($5), $12)
    }
' | tee "Google_$(basename -- "$file")" >&3
