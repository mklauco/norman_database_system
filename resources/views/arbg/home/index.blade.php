<x-app-layout>
  <x-slot name="header">
    @include('arbg.header')
  </x-slot>
  


  <div class="py-4">
    <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow-lg sm:rounded-lg">
        <div class="p-6 text-gray-900">

          <!-- Title -->
          <h1 class="text-2xl font-bold text-gray-800 mb-4">
            NORMAN Antibiotic Resistant Bacteria/Genes Database
          </h1>

          <!-- Description with floated image -->
          <div class="mb-4">
            <img src="{{ asset('images/databases/arbg.jpeg') }}"
                 alt="NORMAN ARB&ARG Database"
                 class="float-left mr-6 mb-4 w-48 md:w-64">

            <p class="text-gray-700 leading-relaxed mb-4 text-justify">
              Treated wastewater, sewage sludge and other solid matrices are increasingly identified as reliable
              alternative sources for a range of applications. Although the reuse practice is accompanied by a number of
              benefits, a number of questions are still open regarding the release of contaminants of emerging concern.
              Current open challenges include the spread of chemicals and biological contaminants (e.g. viral genetic
              material, antibiotic resistance genes and bacteria). The occurrence and the effects of these new type of
              contaminants is unknown.
            </p>

            <p class="text-gray-700 leading-relaxed mb-4 text-justify">
              The NORMAN ARB&ARGs database is an effort to aggregate information regarding the occurrence of these
              contaminants and associate their occurrence with organic contaminants of emerging concern. The data that
              will be contributed is expected to establish baseline concentration levels in wastewater intended for reuse
              and in other matrices. The database was initiated by the ITN MSCA ANSWER project (<a href="http://www.answer-itn.eu/" target="_blank" class="link-lime-text">http://www.answer-itn.eu/</a>)
              and was handed over to NORMAN association WG5.
            </p>
            <div class="clear-both"></div>
          </div>

          <p class="text-gray-700 leading-relaxed mb-4">
            Therefore, NORMAN ARB&ARG database is expected to support
          </p>

          <!-- Numbered List -->
          <ol class="list-decimal list-inside text-gray-700 mb-4 ml-4">
            <li>automated prioritisation of biological risk factors</li>
            <li>use of data in models for large scale projections</li>
            <li>use of data for policy development</li>
            <li>derivation of transparent science-based emission limit values (ELVs) for the target microcontaminants in
              matrices intended for reuse</li>
          </ol>

          <h2 class="text-lg font-bold text-gray-800 mb-2">
            How to submit data
          </h2>
          <p class="text-gray-700 leading-relaxed mb-4 text-justify">
            To include data into the NORMAN ARB&ARG database, please use the DATA COLLECTION TEMPLATES (DCT) which can
            be downloaded at <a href="{{ route('empodat.templates', ['code' => 'arbg']) }}" class="link-lime-text">{{ route('empodat.templates', ['code' => 'arbg']) }}</a>.
          </p>

          <p class="text-gray-700 leading-relaxed mb-4 text-justify">
            The completed DCTs should be sent to the NORMAN ARB&ARG Database Team: Nikiforos Alygizakis
            (<a href="mailto:alygizakis@ei.sk" class="link-lime-text">alygizakis@ei.sk</a>), Despo Fatta-Kassinos
            (<a href="mailto:dfatta@ucy.ac.cy" class="link-lime-text">dfatta@ucy.ac.cy</a>) and Jaroslav Slobodnik
            (<a href="mailto:slobodnik@ei.sk" class="link-lime-text">slobodnik@ei.sk</a>) with
            copy to Lian Lundy (<a href="mailto:L.Lundy@mdx.ac.uk" class="link-lime-text">L.Lundy@mdx.ac.uk</a>) and
            Geneviève Deviller (<a href="mailto:genevieve.deviller@derac.eu" class="link-lime-text">genevieve.deviller@derac.eu</a>)
            for quality check and upload to the web-database.
          </p>

          <hr class="border-gray-300 my-4" />

          <p class="text-gray-700 leading-relaxed mb-4">
            Should you use the ARB&ARG, please cite the platform using the URL
            <a href="https://www.norman-network.com/nds/bacteria/" target="_blank" class="link-lime-text">https://www.norman-network.com/nds/bacteria/</a>
            and refer to the publication
          </p>

          <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
            Nikiforos Alygizakis, Kelsey Ng, Ľuboš Čirka, Thomas Berendonk, Francisco Cerqueira, Eddie Cytryn, Geneviève
            Deviller, Gianuario Fortunato, Iakovos C. Iakovides, Ioannis Kampouris, Irene Michael-Kordatou, Foon Yin
            Lai, Lian Lundy, Celia M. Manaia, Roberto B.M. Marano, Gabriela K. Paulus, Benjamin Piña, Elena Radu, Luigi
            Rizzo, Katarzyna Ślipko, Norbert Kreuzinger, Nikolaos S. Thomaidis, Valentina Ugolini, Ivone Vaz-Moreira,
            Jaroslav Slobodnik, Despo Fatta-Kassinos. Making Waves: <strong>The NORMAN Antibiotic Resistant Bacteria and
              Resistance Genes Database (NORMAN ARB&ARG) – an invitation for collaboration to tackle antibiotic
              resistance.</strong>
            Wat Res, 257, 121689 (2024), DOI: <a href="https://doi.org/10.1016/j.watres.2024.121689" target="_blank"
              class="link-lime">10.1016/j.watres.2024.121689</a>
          </div>


        </div>


      </div>
    </div>
  </div>

</x-app-layout>
