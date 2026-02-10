import { createClient } from '@supabase/supabase-js';

const SUPABASE_URL = 'https://jigpxtgfatwpbhxusijm.supabase.co';
const SUPABASE_ANON_KEY =
  'sb_publishable_Wc91MGJ8QK-AWUPHkw8mkg_GayrN8xl';

export const supabase = createClient(
  SUPABASE_URL,
  SUPABASE_ANON_KEY,
  {
    auth: {
      persistSession: true,
      autoRefreshToken: true,
      detectSessionInUrl: false
    }
  }
);
