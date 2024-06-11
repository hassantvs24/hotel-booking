<thead {{ $attributes->merge([ 'class' => '' ]) }}>
   <x-admin.table-row>
       {{ $slot }}
   </x-admin.table-row>
</thead>