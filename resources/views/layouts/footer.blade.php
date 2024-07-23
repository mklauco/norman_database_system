<!-- Footer container -->
<footer class="text-center text-surface/75 bg-neutral-900 text-white/75 lg:text-left">
  

  <!-- Main container div: holds the entire content of the footer, including four sections (TW Elements, Products, Useful links, and Contact), with responsive styling and appropriate padding/margins. -->
  <div class="mx-6 py-10 text-center md:text-left">
    <div class="grid-1 grid gap-8 md:grid-cols-2 lg:grid-cols-4">
      <!-- TW Elements section -->
      <div class="">
        <h6 class="mb-4 flex items-center justify-center font-semibold uppercase md:justify-start">
          <span class="me-3 [&>svg]:h-4 [&>svg]:w-4">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              viewBox="0 0 24 24"
              fill="currentColor">
              <path
                d="M12.378 1.602a.75.75 0 00-.756 0L3 6.632l9 5.25 9-5.25-8.622-5.03zM21.75 7.93l-9 5.25v9l8.628-5.032a.75.75 0 00.372-.648V7.93zM11.25 22.18v-9l-9-5.25v8.57a.75.75 0 00.372.648l8.628 5.033z" />
            </svg>
          </span>
          NORMAN Database System
        </h6>
        <p>
          NORMAN organises the development and maintenance of various web-based databases for the collection & evaluation of data / information on emerging substances in the environment
        </p>
      </div>
      <!-- Products section -->
      <div>
        <h6
          class="mb-4 flex justify-center font-semibold uppercase md:justify-start">
          Products
        </h6>
        <p class="mb-4">
          <a href="#!">Angular</a>
        </p>
        <p class="mb-4">
          <a href="#!">React</a>
        </p>
        <p class="mb-4">
          <a href="#!">Vue</a>
        </p>
        <p>
          <a href="#!">Laravel</a>
        </p>
      </div>
      <!-- Useful links section -->
      <div>
        <h6
          class="mb-4 flex justify-center font-semibold uppercase md:justify-start">
          Useful links
        </h6>
        <p class="mb-4">
          <a href="#!">Pricing</a>
        </p>
        <p class="mb-4">
          <a href="#!">Settings</a>
        </p>
        <p class="mb-4">
          <a href="#!">Orders</a>
        </p>
        <p>
          <a href="#!">Help</a>
        </p>
      </div>
      <!-- Contact section -->
      <div>
        <h6
          class="mb-4 flex justify-center font-semibold uppercase md:justify-start">
          Contact
        </h6>
        <p class="mb-4 flex items-center justify-center md:justify-start">
          <span class="me-3">
            <i class="fas fa-user-tie"></i>
          </span>
          <span class="px-2">Dr. Jaroslav Slobodnik</span>
        </p>
        <p class="mb-4 flex items-center justify-center md:justify-start">
          <span class="me-3">
            <i class="fas fa-envelope"></i>
          </span>
          <a class="link-lime" href="mailto:slobodnik@ei.sk">slobodnik@ei.sk</a> | <a class="link-lime" href="mailto:norman@ei.sk">norman@ei.sk</a>
        </p>
        <p class="mb-4 flex items-center justify-center md:justify-start">
          <span class="me-3">
            <i class="fas fa-globe"></i>
          </span>
          <a class="link-lime" href="https://www.norman-network.com/">https://www.norman-network.com</a>
        </p>
      </div>
    </div>
  </div>

  <!--Copyright section-->
  <div class="bg-neutral-950 p-2 text-center">
    <span> @php echo date('Y', time()) @endphp © Copyright All Rights Reserved</span>
    <a class="font-semibold link-lime" href="https://www.norman-network.com/">NORMAN website</a>
  </div>
</footer>