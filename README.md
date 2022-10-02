# FilesDuplicate

Will copy a file identified by its id to the root folder of the recipient.


### occ command

>     ./occ <owner> <fileId> [--store] [--to RECIPIENT] [--name COPYNAME]

- `owner` original owner of the file,
- `fileId` id of the file to copy,
- `store` (option) ignore entry from table 'filecache' and get the file directly from the `objectstore`, if configured,
- `to` (option) copy the file to an account other than the defined owner,
- `name` (option) set a specific filename for the copied version of the file. Default: `[COPY] File #<fileId>`

