#!/bin/bash

# Get the source and target directories from the command line arguments
SOURCE_DIR="$1"
TARGET_DIR="$2"
CACHE_FILE="${3}/nc_copy_cache"

# Function to copy file from source to target or cache if target is not mounted
copy_file () {
    local file="$1"
    if mountpoint -q "$TARGET_DIR"; then
        echo "Copying file to $TARGET_DIR"
        cp "$SOURCE_DIR/$file" "$TARGET_DIR"
        # If file was in cache, remove it
        sed -i "/^$file$/d" "$CACHE_FILE"
    else
        echo "$TARGET_DIR is not mounted"
        echo "Caching file for later copy"
        echo "$file" >> "$CACHE_FILE"
    fi
}

# Function to remove file from target or cache
remove_file () {
    local file="$1"
    if mountpoint -q "$TARGET_DIR" && [ -f "$TARGET_DIR/$file" ]; then
        echo "Removing file from $TARGET_DIR"
        rm "$TARGET_DIR/$file"
    fi
    # If file was in cache, remove it
    sed -i "/^$file$/d" "$CACHE_FILE"
}

# Function to process cached files
process_cache () {
    if mountpoint -q "$TARGET_DIR"; then
        echo "Processing cached files..."
        while read -r file; do
            copy_file "$file"
        done < "$CACHE_FILE"
    fi
}

# Initial processing of cache
process_cache

# Watch for changes in source directory
inotifywait -m "$SOURCE_DIR" -e create -e delete |
    while read path action file; do
        case "$action" in
            CREATE)
                echo "The file '$file' was created in '$path'"
                copy_file "$file"
                ;;
            DELETE)
                echo "The file '$file' was deleted from '$path'"
                remove_file "$file"
                ;;
            *)
                ;;
        esac
    done
