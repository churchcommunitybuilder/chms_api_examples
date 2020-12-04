pub struct Env {
  pub client_id: String,
  pub client_secret: String,
  pub subdomain: String,
}

pub fn get_env() -> Env {
  Env {
    client_id: String::from(dotenv!("CLIENT_ID")),
    client_secret: String::from(dotenv!("CLIENT_SECRET")),
    subdomain: String::from(dotenv!("SUBDOMAIN")),
  }
}
