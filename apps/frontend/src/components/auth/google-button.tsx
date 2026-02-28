export function GoogleButton() {
  return (
    <button
      type="button"
      className="flex h-12 w-full items-center justify-center gap-2.5 rounded-pill border border-border bg-background text-sm font-medium text-foreground font-heading transition-colors hover:bg-muted"
    >
      <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path
          d="M17.64 9.2045c0-.6381-.0573-1.2518-.1636-1.8409H9v3.4814h4.8436c-.2086 1.125-.8427 2.0782-1.7959 2.7164v2.2581h2.9087c1.7018-1.5668 2.6836-3.874 2.6836-6.615z"
          fill="#4285F4"
        />
        <path
          d="M9 18c2.43 0 4.4673-.806 5.9564-2.1805l-2.9087-2.2581c-.8059.54-1.8368.859-3.0477.859-2.344 0-4.328-1.584-5.0344-3.7104H.9574v2.3318C2.4382 15.9832 5.4818 18 9 18z"
          fill="#34A853"
        />
        <path
          d="M3.9656 10.71c-.18-.54-.2827-1.1168-.2827-1.71s.1027-1.17.2827-1.71V4.9582H.9574C.3477 6.1732 0 7.5477 0 9s.3477 2.8268.9574 4.0418L3.9656 10.71z"
          fill="#FBBC05"
        />
        <path
          d="M9 3.5795c1.3214 0 2.5077.4541 3.4405 1.346l2.5813-2.5814C13.4636.8918 11.4264 0 9 0 5.4818 0 2.4382 2.0168.9574 4.9582L3.9656 7.29C4.672 5.1636 6.656 3.5795 9 3.5795z"
          fill="#EA4335"
        />
      </svg>
      Continue with Google
    </button>
  );
}
