# Local Docker Deployment

This project uses PHP and MySQL. To run it locally in a containerized environment:

1. Install Docker Desktop.
2. Open a terminal in this folder.
3. Run:
   ```powershell
   docker compose up --build
   ```
4. Visit `http://localhost:8000` in your browser.

The database will be initialized from `database.sql` automatically.

If you want to stop it, run:
```powershell
docker compose down
```

## Online deployment (Render)

This project can also be deployed online using Render with the included `render.yaml` file.

1. Push the project to a Git repository.
2. Create a Render account at `https://render.com`.
3. Connect your repository and deploy the `quiz-app` service.
4. In Render, add the `quiz-app-db` MySQL database service.
5. Render will inject the managed database variables automatically.
6. The app reads either `DB_*` or `DATABASE_*` environment variables.

If you prefer another host such as Fly.io, Railway, or a VPS, the `Dockerfile` is ready for container deployment.
